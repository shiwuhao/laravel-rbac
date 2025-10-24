<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class ScanPermissionAnnotationsCommand extends Command
{
    protected $signature = 'rbac:scan-annotations
                            {--path=* : 扫描的路径（可多个），默认为 app/Actions 和 app/Http/Controllers}
                            {--namespace=* : 命名空间前缀（可多个），默认为 App\\Actions 和 App\\Http\\Controllers}
                            {--package : 扫描扩展包内置的 Actions}
                            {--dry-run : 仅显示将要创建的权限，不实际创建}
                            {--force : 强制覆盖已存在的权限}';

    protected $description = '扫描 Action/Controller 类的权限注解并自动生成权限节点';

    protected array $createdPermissions = [];
    protected array $skippedPermissions = [];
    protected array $errors = [];

    public function handle(): int
    {
        $this->info('开始扫描权限注解...');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $scanPackage = $this->option('package');

        $pathsToScan = $this->getPathsToScan($scanPackage);

        if (empty($pathsToScan)) {
            $this->error('未找到要扫描的路径');
            return Command::FAILURE;
        }

        foreach ($pathsToScan as $pathConfig) {
            $path = $pathConfig['path'];
            $namespace = $pathConfig['namespace'];

            if (!is_dir($path)) {
                $this->warn("路径不存在，跳过: {$path}");
                continue;
            }

            $this->line("\n扫描: {$path}");
            $this->scanDirectory($path, $namespace, $dryRun, $force);
        }

        $this->displayResults();

        return Command::SUCCESS;
    }

    protected function getPathsToScan(bool $scanPackage): array
    {
        $paths = [];

        if ($scanPackage) {
            $paths[] = [
                'path' => __DIR__ . '/../Actions',
                'namespace' => 'Rbac\\Actions',
            ];
            $this->info('扫描扩展包内置 Actions');
        }

        $customPaths = $this->option('path');
        $customNamespaces = $this->option('namespace');

        if (!empty($customPaths)) {
            foreach ($customPaths as $index => $path) {
                $paths[] = [
                    'path' => $path,
                    'namespace' => $customNamespaces[$index] ?? 'App\\Actions',
                ];
            }
        } else {
            $defaultPaths = [
                [
                    'path' => app_path('Actions'),
                    'namespace' => 'App\\Actions',
                ],
                [
                    'path' => app_path('Http/Controllers'),
                    'namespace' => 'App\\Http\\Controllers',
                ],
            ];

            foreach ($defaultPaths as $pathConfig) {
                if (is_dir($pathConfig['path'])) {
                    $paths[] = $pathConfig;
                }
            }
        }

        return $paths;
    }

    protected function scanDirectory(string $path, string $namespace, bool $dryRun, bool $force): void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = str_replace([$path, '.php', '/'], ['', '', '\\'], $file->getRelativePathname());
            $className = $namespace . '\\' . $relativePath;

            if (!class_exists($className)) {
                continue;
            }

            try {
                $this->scanClass($className, $dryRun, $force);
            } catch (\Exception $e) {
                $this->errors[] = "扫描类 {$className} 时出错: " . $e->getMessage();
            }
        }
    }

    protected function scanClass(string $className, bool $dryRun, bool $force): void
    {
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes();

        $permissionGroupData = null;
        $permissions = [];

        foreach ($attributes as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof PermissionGroup) {
                $permissionGroupData = [
                    'slug' => $attributeInstance->slug,
                    'name' => $attributeInstance->name,
                ];
            }

            if ($attributeInstance instanceof Permission) {
                $permissions[] = [
                    'slug' => $attributeInstance->slug,
                    'name' => $attributeInstance->name,
                ];
            }
        }

        foreach ($permissions as $permissionData) {
            $this->createPermissionFromAnnotation(
                $permissionData,
                $permissionGroupData,
                $className,
                $dryRun,
                $force
            );
        }
    }

    protected function createPermissionFromAnnotation(
        array $permissionData,
        ?array $groupData,
        string $className,
        bool $dryRun,
        bool $force
    ): void {
        $slug = $permissionData['slug'];
        $name = $permissionData['name'];

        $parts = explode(':', $slug);
        $resourceType = $parts[0] ?? 'unknown';
        $operation = $parts[1] ?? 'unknown';

        $existingPermission = \Rbac\Models\Permission::where('slug', $slug)->first();

        if ($existingPermission && !$force) {
            $this->skippedPermissions[] = [
                'slug' => $slug,
                'reason' => '已存在',
                'class' => $className,
            ];
            return;
        }

        if ($dryRun) {
            $this->createdPermissions[] = [
                'slug' => $slug,
                'name' => $name,
                'resource_type' => $resourceType,
                'operation' => $operation,
                'class' => $className,
                'group' => $groupData ? $groupData['name'] : null,
                'action' => $existingPermission ? '更新' : '创建',
            ];
            return;
        }

        try {
            if ($existingPermission && $force) {
                $existingPermission->update([
                    'name' => $name,
                    'resource_type' => $resourceType,
                    'operation' => $operation,
                ]);
                $action = '更新';
            } else {
                CreatePermission::handle([
                    'name' => $name,
                    'slug' => $slug,
                    'resource_type' => $resourceType,
                    'operation' => $operation,
                    'guard_name' => 'web',
                ]);
                $action = '创建';
            }

            $this->createdPermissions[] = [
                'slug' => $slug,
                'name' => $name,
                'resource_type' => $resourceType,
                'operation' => $operation,
                'class' => $className,
                'group' => $groupData ? $groupData['name'] : null,
                'action' => $action,
            ];
        } catch (\Exception $e) {
            $this->errors[] = "创建权限 {$slug} 时出错: " . $e->getMessage();
        }
    }

    protected function displayResults(): void
    {
        if (!empty($this->createdPermissions)) {
            $this->info("\n=== 权限处理结果 ===");
            $this->table(
                ['操作', '权限标识', '权限名称', '资源类型', '操作类型', '权限组', 'Action 类'],
                array_map(function ($item) {
                    return [
                        $item['action'],
                        $item['slug'],
                        $item['name'],
                        $item['resource_type'],
                        $item['operation'],
                        $item['group'] ?? '-',
                        class_basename($item['class']),
                    ];
                }, $this->createdPermissions)
            );
            $this->info("共处理 " . count($this->createdPermissions) . " 个权限");
        }

        if (!empty($this->skippedPermissions)) {
            $this->warn("\n=== 跳过的权限 ===");
            $this->table(
                ['权限标识', '原因', 'Action 类'],
                array_map(function ($item) {
                    return [
                        $item['slug'],
                        $item['reason'],
                        class_basename($item['class']),
                    ];
                }, $this->skippedPermissions)
            );
            $this->warn("共跳过 " . count($this->skippedPermissions) . " 个权限");
        }

        if (!empty($this->errors)) {
            $this->error("\n=== 错误 ===");
            foreach ($this->errors as $error) {
                $this->error($error);
            }
        }

        if (empty($this->createdPermissions) && empty($this->skippedPermissions)) {
            $this->warn("未找到任何权限注解");
        }
    }
}

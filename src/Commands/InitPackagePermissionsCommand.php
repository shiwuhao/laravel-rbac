<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * 初始化扩展包权限命令
 * 
 * 扫描扩展包内置的 Action 类并自动创建权限节点
 * 适用于首次安装或更新扩展包时使用
 */
class InitPackagePermissionsCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:init-permissions
                            {--force : 强制覆盖已存在的权限}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化扩展包内置的权限节点';

    /**
     * 创建的权限列表
     *
     * @var array
     */
    protected array $permissions = [];

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('开始初始化扩展包权限...');

        $force = $this->option('force');

        $this->scanPackageActions($force);

        if (empty($this->permissions)) {
            $this->warn('未找到任何权限');
            return Command::SUCCESS;
        }

        $this->info("\n成功初始化 " . count($this->permissions) . " 个权限节点");
        
        $this->table(
            ['权限标识', '权限名称', '资源', '操作', '权限组'],
            array_map(function ($item) {
                return [
                    $item['slug'],
                    $item['name'],
                    $item['resource'],
                    $item['operation'],
                    $item['group'] ?? '-',
                ];
            }, $this->permissions)
        );

        return Command::SUCCESS;
    }

    /**
     * 扫描扩展包内置的 Actions
     *
     * @param bool $force 是否强制覆盖
     * @return void
     */
    protected function scanPackageActions(bool $force): void
    {
        $path = __DIR__ . '/../Actions';
        $namespace = 'Rbac\\Actions';

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = str_replace([$path, '.php', '/'], ['', '', '\\'], $file->getRelativePathname());
            $className = $namespace . '\\' . $relativePath;

            if (!class_exists($className)) {
                continue;
            }

            try {
                $this->processClass($className, $force);
            } catch (\Exception $e) {
                $this->warn("处理类 {$className} 时出错: " . $e->getMessage());
            }
        }
    }

    /**
     * 处理单个类的权限注解
     *
     * @param string $className 类名
     * @param bool $force 是否强制覆盖
     * @return void
     */
    protected function processClass(string $className, bool $force): void
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
            $this->createPermission($permissionData, $permissionGroupData, $force);
        }
    }

    /**
     * 创建权限
     *
     * @param array $permissionData 权限数据
     * @param array|null $groupData 权限组数据
     * @param bool $force 是否强制覆盖
     * @return void
     */
    protected function createPermission(
        array $permissionData,
        ?array $groupData,
        bool $force
    ): void {
        $slug = $permissionData['slug'];
        $name = $permissionData['name'];

        $parts = explode(':', $slug);
        $resourceType = $parts[0] ?? 'unknown';
        $operation = $parts[1] ?? 'unknown';

        $existingPermission = \Rbac\Models\Permission::where('slug', $slug)->first();

        if ($existingPermission && !$force) {
            return;
        }

        try {
            if ($existingPermission && $force) {
                $existingPermission->update([
                    'name' => $name,
                    'resource_type' => $resourceType,
                    'operation' => $operation,
                ]);
            } else {
                CreatePermission::handle([
                    'name' => $name,
                    'slug' => $slug,
                    'resource_type' => $resourceType,
                    'operation' => $operation,
                    'description' => '扩展包内置权限',
                    'guard_name' => 'web',
                ]);
            }

            $this->permissions[] = [
                'slug' => $slug,
                'name' => $name,
                'resource' => $resourceType,
                'operation' => $operation,
                'group' => $groupData ? $groupData['name'] : null,
            ];
        } catch (\Exception $e) {
            $this->warn("创建权限 {$slug} 失败: " . $e->getMessage());
        }
    }
}

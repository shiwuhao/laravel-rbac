<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission as PermissionModel;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * 扫描权限注解命令
 * 
 * 扫描 Action 类上的权限注解，自动生成权限节点
 * 适用于基于注解的权限管理方式
 * 
 * @example php artisan rbac:scan-permissions
 * @example php artisan rbac:scan-permissions --force
 */
class ScanPermissionsCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:scan-permissions
                            {--force : 强制覆盖已存在的权限}
                            {--dry-run : 仅显示将要创建的权限}
                            {--routes : 同时扫描路由注解}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '扫描 Action 类的权限注解并自动生成权限节点';

    /**
     * 创建的权限列表
     *
     * @var array
     */
    protected array $created = [];

    /**
     * 跳过的权限列表
     *
     * @var array
     */
    protected array $skipped = [];

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('开始扫描权限注解...');

        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $scanRoutes = $this->option('routes');

        // 扫描扩展包内置的 Actions
        $this->scanDirectory(
            __DIR__ . '/../Actions',
            'Rbac\\Actions',
            $force,
            $dryRun
        );

        // 扫描用户项目的 Actions（如果存在）
        if (is_dir(app_path('Actions/Rbac'))) {
            $this->info("\n扫描项目 Actions...");
            $this->scanDirectory(
                app_path('Actions/Rbac'),
                'App\\Actions\\Rbac',
                $force,
                $dryRun
            );
        }

        // 扫描路由注解
        if ($scanRoutes) {
            $this->info("\n扫描路由注解...");
            $this->scanRoutes($force, $dryRun);
        }

        $this->displayResults($dryRun);

        return Command::SUCCESS;
    }

    /**
     * 扫描目录
     *
     * @param string $path 扫描路径
     * @param string $namespace 命名空间
     * @param bool $force 是否强制覆盖
     * @param bool $dryRun 是否预览模式
     * @return void
     */
    protected function scanDirectory(string $path, string $namespace, bool $force, bool $dryRun): void
    {
        if (!is_dir($path)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = str_replace(
                [$path, '.php', '/'],
                ['', '', '\\'],
                $file->getRelativePathname()
            );
            $className = $namespace . '\\' . $relativePath;

            if (!class_exists($className)) {
                continue;
            }

            try {
                $this->scanClass($className, $force, $dryRun);
            } catch (\Exception $e) {
                $this->warn("处理类 {$className} 时出错: " . $e->getMessage());
            }
        }
    }

    /**
     * 扫描单个类
     *
     * @param string $className 类名
     * @param bool $force 是否强制覆盖
     * @param bool $dryRun 是否预览模式
     * @return void
     */
    protected function scanClass(string $className, bool $force, bool $dryRun): void
    {
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes();

        $groupData = null;

        // 查找权限组注解
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === PermissionGroup::class) {
                $instance = $attribute->newInstance();
                $groupData = [
                    'slug' => $instance->slug,
                    'name' => $instance->name,
                ];
            }
        }

        // 查找权限注解
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Permission::class) {
                $instance = $attribute->newInstance();
                $this->processPermission(
                    $instance->slug,
                    $instance->name,
                    $groupData,
                    $className,
                    $force,
                    $dryRun,
                    $instance->description ?? null
                );
            }
        }
    }

    /**
     * 处理权限
     *
     * @param string $slug 权限标识
     * @param string|null $name 权限名称
     * @param array|null $groupData 权限组数据
     * @param string $className 类名
     * @param bool $force 是否强制覆盖
     * @param bool $dryRun 是否预览模式
     * @param string|null $description 权限描述
     * @return void
     */
    protected function processPermission(
        string $slug,
        ?string $name,
        ?array $groupData,
        string $className,
        bool $force,
        bool $dryRun,
        ?string $description = null
    ): void {
        // 解析 slug 获取 resource 和 action
        $parts = explode(':', $slug);
        $resource = $parts[0] ?? 'unknown';
        $action = $parts[1] ?? 'unknown';

        // 生成权限名称
        $permissionName = $name ?? $this->generatePermissionName($resource, $action);

        // 检查权限是否已存在
        $existing = PermissionModel::where('slug', $slug)->first();

        if ($existing && !$force) {
            $this->skipped[] = [
                'slug' => $slug,
                'name' => $permissionName,
                'reason' => '已存在',
                'class' => class_basename($className),
            ];
            return;
        }

        if ($dryRun) {
            $this->created[] = [
                'slug' => $slug,
                'name' => $permissionName,
                'resource' => $resource,
                'action' => $action,
                'group' => $groupData['name'] ?? null,
                'class' => class_basename($className),
                'status' => $existing ? '更新' : '新建',
            ];
            return;
        }

        // 创建或更新权限
        try {
            if ($existing && $force) {
                $existing->update([
                    'name' => $permissionName,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $description ?? ($groupData ? "权限组: {$groupData['name']}" : '注解生成的权限'),
                ]);
                $status = '更新';
            } else {
                PermissionModel::create([
                    'name' => $permissionName,
                    'slug' => $slug,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $description ?? ($groupData ? "权限组: {$groupData['name']}" : '注解生成的权限'),
                    'guard_name' => 'web',
                ]);
                $status = '新建';
            }

            $this->created[] = [
                'slug' => $slug,
                'name' => $permissionName,
                'resource' => $resource,
                'action' => $action,
                'group' => $groupData['name'] ?? null,
                'class' => class_basename($className),
                'status' => $status,
            ];
        } catch (\Exception $e) {
            $this->skipped[] = [
                'slug' => $slug,
                'name' => $permissionName,
                'reason' => $e->getMessage(),
                'class' => class_basename($className),
            ];
        }
    }

    /**
     * 生成权限名称
     *
     * @param string $resource 资源
     * @param string $action 操作
     * @return string
     */
    protected function generatePermissionName(string $resource, string $action): string
    {
        $actionLabels = [
            'view' => '查看',
            'create' => '创建',
            'update' => '更新',
            'delete' => '删除',
            'list' => '列表',
            'show' => '详情',
            'store' => '保存',
            'destroy' => '删除',
        ];

        $resourceLabels = [
            'role' => '角色',
            'permission' => '权限',
            'user' => '用户',
            'data-scope' => '数据范围',
        ];

        $actionLabel = $actionLabels[$action] ?? ucfirst($action);
        $resourceLabel = $resourceLabels[$resource] ?? ucfirst($resource);

        return "{$actionLabel}{$resourceLabel}";
    }

    /**
     * 显示结果
     *
     * @param bool $dryRun 是否预览模式
     * @return void
     */
    protected function displayResults(bool $dryRun): void
    {
        $this->info("\n" . ($dryRun ? '=== 预览模式 ===' : '=== 扫描结果 ==='));

        if (!empty($this->created)) {
            $this->info("\n" . ($dryRun ? '将要处理' : '已处理') . " {$this->count($this->created)} 个权限:");
            $this->table(
                ['权限标识', '权限名称', '资源', '操作', '权限组', 'Action 类', '状态'],
                array_map(function ($item) {
                    return [
                        $item['slug'],
                        $item['name'],
                        $item['resource'],
                        $item['action'],
                        $item['group'] ?? '-',
                        $item['class'],
                        $item['status'],
                    ];
                }, $this->created)
            );
        }

        if (!empty($this->skipped)) {
            $this->warn("\n跳过 {$this->count($this->skipped)} 个权限:");
            $this->table(
                ['权限标识', '权限名称', '原因', 'Action 类'],
                array_map(function ($item) {
                    return [
                        $item['slug'],
                        $item['name'],
                        $item['reason'],
                        $item['class'],
                    ];
                }, $this->skipped)
            );
        }

        if (empty($this->created) && empty($this->skipped)) {
            $this->info("\n未找到任何权限注解");
        }

        if ($dryRun && !empty($this->created)) {
            $this->info("\n💡 使用 --force 参数强制覆盖已存在的权限");
            $this->info("💡 去掉 --dry-run 参数执行实际创建");
        }
    }

    /**
     * 统计数量
     *
     * @param array $items 项目列表
     * @return int
     */
    protected function count(array $items): int
    {
        return count($items);
    }

    /**
     * 扫描路由注解
     *
     * @param bool $force 是否强制覆盖
     * @param bool $dryRun 是否预览模式
     * @return void
     */
    protected function scanRoutes(bool $force, bool $dryRun): void
    {
        $routes = app('router')->getRoutes();

        foreach ($routes as $route) {
            $action = $route->getAction();

            // 处理 Action 模式
            if (isset($action['uses']) && is_string($action['uses'])) {
                $this->scanRouteAction($action['uses'], $route, $force, $dryRun);
            }

            // 处理 Controller 模式
            if (isset($action['controller'])) {
                // 检查是否包含 @ 分隔符
                if (str_contains($action['controller'], '@')) {
                    [$controller, $method] = explode('@', $action['controller']);
                    $this->scanRouteMethod($controller, $method, $route, $force, $dryRun);
                }
            }
        }
    }

    /**
     * 扫描路由 Action
     *
     * @param string $className
     * @param mixed $route
     * @param bool $force
     * @param bool $dryRun
     * @return void
     */
    protected function scanRouteAction(string $className, $route, bool $force, bool $dryRun): void
    {
        if (!class_exists($className)) {
            return;
        }

        try {
            $reflection = new ReflectionClass($className);
            $attributes = $reflection->getAttributes(Permission::class);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $this->processPermission(
                    $instance->slug,
                    $instance->name,
                    null,
                    $className . ' [路由]',
                    $force,
                    $dryRun,
                    $instance->description
                );
            }
        } catch (\Exception $e) {
            // 忽略异常
        }
    }

    /**
     * 扫描路由控制器方法
     *
     * @param string $className
     * @param string $methodName
     * @param mixed $route
     * @param bool $force
     * @param bool $dryRun
     * @return void
     */
    protected function scanRouteMethod(
        string $className,
        string $methodName,
        $route,
        bool $force,
        bool $dryRun
    ): void {
        if (!class_exists($className)) {
            return;
        }

        try {
            $reflection = new ReflectionClass($className);

            // 检查方法注解
            if ($reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                $attributes = $method->getAttributes(Permission::class);

                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    $this->processPermission(
                        $instance->slug,
                        $instance->name,
                        null,
                        class_basename($className) . '@' . $methodName . ' [路由]',
                        $force,
                        $dryRun,
                        $instance->description
                    );
                }
            }
        } catch (\Exception $e) {
            // 忽略异常
        }
    }
}

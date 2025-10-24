<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Models\Permission;

/**
 * 从路由同步权限命令
 * 
 * 基于 Laravel 路由自动生成和同步权限节点
 * 支持筛选、排除和清理孤立权限
 */
class SyncPermissionsFromRoutesCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:sync-routes
                            {--prefix= : 路由前缀筛选，如 api/admin}
                            {--middleware= : 中间件筛选，如 auth}
                            {--name= : 路由名称模式，支持通配符}
                            {--exclude= : 排除的路由名称模式（逗号分隔）}
                            {--clean : 清理不存在的路由权限}
                            {--dry-run : 预览模式}';

    protected $description = '从路由自动同步生成权限节点';

    protected array $created = [];
    protected array $updated = [];
    protected array $skipped = [];
    protected array $deleted = [];

    protected array $defaultExcludes = [
        'debugbar.*',
        'telescope.*',
        'horizon.*',
        'ignition.*',
        '_ignition.*',
        'livewire.*',
        'sanctum.*',
    ];

    public function handle(): int
    {
        $this->info('开始同步路由权限...');

        $routes = $this->getFilteredRoutes();
        $dryRun = $this->option('dry-run');

        if ($routes->isEmpty()) {
            $this->warn('未找到符合条件的路由');
            return Command::SUCCESS;
        }

        $this->info("找到 {$routes->count()} 条路由");

        foreach ($routes as $route) {
            $this->processRoute($route, $dryRun);
        }

        if ($this->option('clean')) {
            $this->cleanOrphanedPermissions($dryRun);
        }

        $this->displayResults();

        return Command::SUCCESS;
    }

    protected function getFilteredRoutes()
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            if (!$route->getName()) {
                return false;
            }

            if ($this->shouldExclude($route->getName())) {
                return false;
            }

            if ($prefix = $this->option('prefix')) {
                if (!str_starts_with($route->uri(), $prefix)) {
                    return false;
                }
            }

            if ($middleware = $this->option('middleware')) {
                if (!in_array($middleware, $route->middleware())) {
                    return false;
                }
            }

            if ($name = $this->option('name')) {
                if (!fnmatch($name, $route->getName())) {
                    return false;
                }
            }

            return true;
        });

        return $routes;
    }

    protected function shouldExclude(string $routeName): bool
    {
        $excludes = $this->defaultExcludes;

        if ($customExcludes = $this->option('exclude')) {
            $excludes = array_merge($excludes, explode(',', $customExcludes));
        }

        foreach ($excludes as $pattern) {
            if (fnmatch(trim($pattern), $routeName)) {
                return true;
            }
        }

        return false;
    }

    protected function processRoute($route, bool $dryRun): void
    {
        $routeName = $route->getName();
        $methods = $route->methods();
        $uri = $route->uri();

        $parts = explode('.', $routeName);
        $resourceType = $parts[0] ?? 'unknown';
        $operation = $parts[1] ?? $this->guessOperationFromMethod($methods[0] ?? 'GET');

        $slug = str_replace('.', ':', $routeName);
        $name = $this->generatePermissionName($resourceType, $operation);

        $existingPermission = Permission::where('slug', $slug)->first();

        if ($existingPermission) {
            if ($existingPermission->name !== $name || 
                $existingPermission->resource_type !== $resourceType ||
                $existingPermission->operation !== $operation) {
                
                if (!$dryRun) {
                    $existingPermission->update([
                        'name' => $name,
                        'resource_type' => $resourceType,
                        'operation' => $operation,
                    ]);
                }
                
                $this->updated[] = [
                    'slug' => $slug,
                    'name' => $name,
                    'route' => $routeName,
                    'uri' => $uri,
                    'methods' => implode('|', $methods),
                ];
            } else {
                $this->skipped[] = [
                    'slug' => $slug,
                    'reason' => '无变化',
                    'route' => $routeName,
                ];
            }
            return;
        }

        if (!$dryRun) {
            try {
                CreatePermission::handle([
                    'name' => $name,
                    'slug' => $slug,
                    'resource_type' => $resourceType,
                    'operation' => $operation,
                    'description' => "路由权限: {$routeName}",
                    'guard_name' => 'web',
                ]);
            } catch (\Exception $e) {
                $this->skipped[] = [
                    'slug' => $slug,
                    'reason' => $e->getMessage(),
                    'route' => $routeName,
                ];
                return;
            }
        }

        $this->created[] = [
            'slug' => $slug,
            'name' => $name,
            'resource' => $resourceType,
            'operation' => $operation,
            'route' => $routeName,
            'uri' => $uri,
            'methods' => implode('|', $methods),
        ];
    }

    protected function guessOperationFromMethod(string $method): string
    {
        return match(strtoupper($method)) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'access',
        };
    }

    protected function generatePermissionName(string $resource, string $operation): string
    {
        $operationLabels = [
            'index' => '列表',
            'view' => '查看',
            'show' => '详情',
            'create' => '创建',
            'store' => '保存',
            'update' => '更新',
            'edit' => '编辑',
            'delete' => '删除',
            'destroy' => '删除',
        ];

        $operationLabel = $operationLabels[$operation] ?? $operation;
        $resourceLabel = ucfirst($resource);

        return "{$operationLabel}{$resourceLabel}";
    }

    protected function cleanOrphanedPermissions(bool $dryRun): void
    {
        $this->info("\n检查孤立权限...");

        $allRouteNames = collect(Route::getRoutes())
            ->filter(fn($r) => $r->getName())
            ->map(fn($r) => str_replace('.', ':', $r->getName()))
            ->toArray();

        $orphaned = Permission::whereNotNull('resource_type')
            ->get()
            ->filter(function ($permission) use ($allRouteNames) {
                return !in_array($permission->slug, $allRouteNames) && 
                       str_contains($permission->description ?? '', '路由权限');
            });

        if ($orphaned->isEmpty()) {
            $this->info('未找到孤立权限');
            return;
        }

        foreach ($orphaned as $permission) {
            $this->deleted[] = [
                'slug' => $permission->slug,
                'name' => $permission->name,
            ];

            if (!$dryRun) {
                $permission->delete();
            }
        }
    }

    protected function displayResults(): void
    {
        if (!empty($this->created)) {
            $this->info("\n=== 创建的权限 ({count}) ===");
            $this->table(
                ['权限标识', '权限名称', '资源', '操作', '路由名', 'URI', '方法'],
                array_map(fn($item) => [
                    $item['slug'],
                    $item['name'],
                    $item['resource'],
                    $item['operation'],
                    $item['route'],
                    $item['uri'],
                    $item['methods'],
                ], $this->created)
            );
            $this->info("共创建 " . count($this->created) . " 个权限");
        }

        if (!empty($this->updated)) {
            $this->info("\n=== 更新的权限 ===");
            $this->table(
                ['权限标识', '权限名称', '路由名', 'URI', '方法'],
                array_map(fn($item) => [
                    $item['slug'],
                    $item['name'],
                    $item['route'],
                    $item['uri'],
                    $item['methods'],
                ], $this->updated)
            );
            $this->warn("共更新 " . count($this->updated) . " 个权限");
        }

        if (!empty($this->deleted)) {
            $this->warn("\n=== 删除的孤立权限 ===");
            $this->table(
                ['权限标识', '权限名称'],
                array_map(fn($item) => [$item['slug'], $item['name']], $this->deleted)
            );
            $this->warn("共删除 " . count($this->deleted) . " 个权限");
        }

        if (!empty($this->skipped)) {
            $this->line("\n=== 跳过的权限 ===");
            $this->line("共跳过 " . count($this->skipped) . " 个");
        }

        $this->info("\n=== 总结 ===");
        $this->line("创建: " . count($this->created));
        $this->line("更新: " . count($this->updated));
        $this->line("删除: " . count($this->deleted));
        $this->line("跳过: " . count($this->skipped));
    }
}

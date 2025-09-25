<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Services\RoutePermissionService;

/**
 * 生成路由权限命令
 */
class GenerateRoutePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:generate-route-permissions
                            {--pattern= : 路由名称模式 (支持通配符)}
                            {--clean : 清理孤立的路由权限}
                            {--force : 强制重新生成}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据路由自动生成权限节点';

    protected RoutePermissionService $routePermissionService;

    public function __construct(RoutePermissionService $routePermissionService)
    {
        parent::__construct();
        $this->routePermissionService = $routePermissionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pattern = $this->option('pattern');
        $clean = $this->option('clean');
        $force = $this->option('force');

        try {
            $this->info('开始生成路由权限...');

            if ($pattern) {
                $permissions = $this->routePermissionService->generatePermissionsByPattern($pattern);
                $this->info("按模式 '{$pattern}' 生成了 {$permissions->count()} 个权限");
            } else {
                $permissions = $this->routePermissionService->generateAllRoutePermissions($clean);
                $this->info("生成了 {$permissions->count()} 个路由权限");
            }

            if ($permissions->isNotEmpty()) {
                $this->displayPermissions($permissions);
            } else {
                $this->warn('没有生成任何权限');
            }

            // 显示统计信息
            $stats = $this->routePermissionService->getRoutePermissionStats();
            $this->displayStats($stats);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("生成路由权限失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 显示生成的权限
     */
    protected function displayPermissions($permissions): void
    {
        $headers = ['ID', '名称', '标识符', '资源', '操作', '守卫'];
        $rows = $permissions->map(function ($permission) {
            return [
                $permission->id,
                $permission->name,
                $permission->slug,
                $permission->resource,
                $permission->action,
                $permission->guard_name,
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    /**
     * 显示统计信息
     */
    protected function displayStats(array $stats): void
    {
        $this->info("\n=== 路由权限统计 ===");
        $this->line("总路由数: {$stats['total_routes']}");
        $this->line("路由权限数: {$stats['route_permissions']}");
        $this->line("覆盖率: {$stats['coverage_percentage']}%");
    }
}
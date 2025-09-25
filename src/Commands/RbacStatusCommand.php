<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Services\RbacService;

/**
 * RBAC 状态命令
 */
class RbacStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '显示 RBAC 系统状态和统计信息';

    protected RbacService $rbacService;

    public function __construct(RbacService $rbacService)
    {
        parent::__construct();
        $this->rbacService = $rbacService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('=== RBAC 系统状态 ===');

            // 获取统计信息
            $stats = $this->rbacService->getPermissionStats();

            $this->displayOverallStats($stats);
            $this->displayResourceStats($stats);
            $this->displayActionStats($stats);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("获取状态信息失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 显示总体统计
     */
    protected function displayOverallStats(array $stats): void
    {
        $this->info("\n总体统计:");
        $this->table(['项目', '数量'], [
            ['角色总数', $stats['total_roles']],
            ['权限总数', $stats['total_permissions']],
            ['数据范围总数', $stats['total_data_scopes']],
        ]);
    }

    /**
     * 显示资源统计
     */
    protected function displayResourceStats(array $stats): void
    {
        if (!empty($stats['permissions_by_resource'])) {
            $this->info("\n按资源类型统计:");
            $rows = [];
            foreach ($stats['permissions_by_resource'] as $resource => $count) {
                $rows[] = [$resource, $count];
            }
            $this->table(['资源类型', '权限数量'], $rows);
        }
    }

    /**
     * 显示操作统计
     */
    protected function displayActionStats(array $stats): void
    {
        if (!empty($stats['permissions_by_action'])) {
            $this->info("\n按操作类型统计:");
            $rows = [];
            foreach ($stats['permissions_by_action'] as $action => $count) {
                $rows[] = [$action, $count];
            }
            $this->table(['操作类型', '权限数量'], $rows);
        }
    }
}
<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Models\Permission;

/**
 * 列出权限命令
 * 
 * 支持多种筛选条件查看权限列表，方便快速定位和管理权限
 * 
 * @example php artisan rbac:list-permissions --resource=user --limit=10
 */
class ListPermissionsCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:list-permissions
                            {--resource= : 按资源类型筛选}
                            {--action= : 按操作类型筛选}
                            {--guard= : 按守卫筛选}
                            {--search= : 搜索关键词}
                            {--limit=20 : 显示数量限制}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '列出所有权限';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $query = Permission::query();

        // 应用筛选条件
        if ($resource = $this->option('resource')) {
            $query->where('resource', $resource);
        }

        if ($action = $this->option('action')) {
            $query->where('action', $action);
        }

        if ($guard = $this->option('guard')) {
            $query->where('guard_name', $guard);
        }

        if ($search = $this->option('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $limit = (int) $this->option('limit');
        $permissions = $query->limit($limit)->get();

        if ($permissions->isEmpty()) {
            $this->warn('未找到任何权限');
            return Command::SUCCESS;
        }

        $this->info("找到 {$permissions->count()} 个权限:");
        
        $this->table(
            ['ID', '名称', '标识符', '资源', '操作', '守卫'],
            $permissions->map(function ($permission) {
                return [
                    $permission->id,
                    $permission->name,
                    $permission->slug,
                    $permission->resource ?? '-',
                    $permission->action ?? '-',
                    $permission->guard_name,
                ];
            })->toArray()
        );

        // 显示总数信息
        $total = Permission::count();
        if ($permissions->count() < $total) {
            $this->line("\n显示 {$permissions->count()} / {$total} 个权限");
            $this->line("使用 --limit 选项查看更多");
        }

        return Command::SUCCESS;
    }
}

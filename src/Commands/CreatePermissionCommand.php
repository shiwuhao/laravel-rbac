<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Models\Permission;

/**
 * 创建权限命令
 * 
 * 用于手动创建单个权限节点，支持自定义资源和操作类型
 * 
 * @example php artisan rbac:create-permission "查看用户" "user.view" --resource=user --action=view
 */
class CreatePermissionCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:create-permission
                            {name : 权限名称}
                            {slug : 权限标识符}
                            {--resource= : 资源类型}
                            {--action= : 操作类型}
                            {--description= : 权限描述}
                            {--guard=web : 守卫名称}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '创建新权限';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug');
        $resource = $this->option('resource');
        $action = $this->option('action');
        $description = $this->option('description');
        $guard = $this->option('guard');

        try {
            // 检查权限是否已存在
            if (Permission::where('slug', $slug)->where('guard_name', $guard)->exists()) {
                $this->error("权限 '{$slug}' 在守卫 '{$guard}' 下已存在！");
                return Command::FAILURE;
            }

            // 使用 Action 创建权限
            $permission = CreatePermission::handle([
                'name' => $name,
                'slug' => $slug,
                'resource' => $resource,
                'action' => $action,
                'description' => $description,
                'guard_name' => $guard,
            ]);

            $this->info("权限 '{$name}' 创建成功！");
            $this->table(['ID', '名称', '标识符', '资源', '操作', '守卫'], [
                [
                    $permission->id,
                    $permission->name,
                    $permission->slug,
                    $permission->resource ?? '-',
                    $permission->action ?? '-',
                    $permission->guard_name,
                ]
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("创建权限失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

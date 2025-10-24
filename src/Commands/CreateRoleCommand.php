<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Actions\Role\CreateRole;
use Rbac\Actions\Role\AssignRolePermissions;
use Rbac\Models\Role;

/**
 * 创建角色命令
 * 
 * 用于手动创建角色并可选地分配权限
 */
class CreateRoleCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:create-role 
                            {name : 角色名称}
                            {slug : 角色标识符}
                            {--description= : 角色描述}
                            {--guard=web : 守卫名称}
                            {--permissions= : 权限列表(逗号分隔的权限 ID)}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '创建新角色';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug');
        $description = $this->option('description');
        $guard = $this->option('guard');
        $permissions = $this->option('permissions');

        try {
            // 检查角色是否已存在
            if (Role::where('slug', $slug)->where('guard_name', $guard)->exists()) {
                $this->error("角色 '{$slug}' 在守卫 '{$guard}' 下已存在！");
                return Command::FAILURE;
            }

            // 使用 Action 创建角色
            $role = CreateRole::handle([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'guard_name' => $guard,
            ]);

            $this->info("角色 '{$name}' 创建成功！");
            $this->table(['ID', '名称', '标识符', '描述', '守卫'], [
                [$role->id, $role->name, $role->slug, $role->description ?? '-', $role->guard_name]
            ]);

            // 分配权限（如果提供）
            if ($permissions) {
                $this->assignPermissions($role, $permissions);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("创建角色失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 分配权限给角色
     *
     * @param Role $role 角色实例
     * @param string $permissions 权限 ID 列表（逗号分隔）
     * @return void
     */
    protected function assignPermissions(Role $role, string $permissions): void
    {
        $permissionIds = array_map('trim', explode(',', $permissions));
        
        try {
            AssignRolePermissions::handle([
                'permission_ids' => $permissionIds,
                'replace' => false,
            ], $role->id);

            $this->info("成功为角色分配了 " . count($permissionIds) . " 个权限");
        } catch (\Exception $e) {
            $this->warn("分配权限时出错: " . $e->getMessage());
        }
    }
}

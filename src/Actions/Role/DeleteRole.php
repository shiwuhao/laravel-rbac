<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 删除角色
 *
 * @example
 * DeleteRole::handle([], $roleId);
 * @example 强制删除（即使有用户关联）
 * DeleteRole::handle(['force' => true], $roleId);
 */
#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:delete', '删除角色')]
class DeleteRole extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'force' => 'sometimes|boolean',
        ];
    }

    /**
     * 删除角色
     *
     * @return array{deleted: bool, detached_users: int}
     *
     * @throws \Exception
     */
    protected function execute(): array
    {
        $roleModel = config('rbac.models.role');
        $role = $roleModel::findOrFail($this->context->id());

        // 检查是否有用户关联
        $usersCount = $role->users()->count();
        $force = $this->context->data('force', false);

        if ($usersCount > 0 && ! $force) {
            throw new \Exception("角色正被 {$usersCount} 个用户使用，请先解除关联或使用强制删除");
        }

        // 清除关联用户的权限缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });

        // 解除所有关联
        $role->permissions()->detach();
        $role->users()->detach();

        $role->forceDelete();

        return ['deleted' => true, 'detached_users' => $usersCount];
    }
}

<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 撤销角色权限（批量）
 *
 * @example
 * RevokePermissionsFromRole::handle([
 *     'permission_ids' => [1, 2, 3],
 * ], $roleId);
 */
#[Permission('role:revoke-permissions', '撤销角色权限')]
class RevokePermissionsFromRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $permissionTable = config('rbac.tables.permissions');

        return [
            'permission_ids' => 'required|array',
            'permission_ids.*' => "exists:{$permissionTable},id",
        ];
    }

    /**
     * 执行撤销（批量）
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $role = $roleModel::findOrFail($this->context->id());

        $permissionIds = array_values(array_unique(array_map('intval', $this->context->data('permission_ids'))));

        $role->permissions()->detach($permissionIds);

        // 清除关联用户的权限缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });

        return $role->load(['permissions', 'users']);
    }
}

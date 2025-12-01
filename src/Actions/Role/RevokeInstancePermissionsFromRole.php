<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 撤销角色的实例权限（批量）
 *
 * @example
 * RevokeInstancePermissionsFromRole::handle([
 *     'permissions' => [
 *         ['slug' => 'report:access', 'resource_type' => 'report', 'resource_id' => 123],
 *         ['slug' => 'report:access', 'resource_type' => 'report', 'resource_id' => 124],
 *     ],
 * ], $roleId);
 */
#[Permission('role:revoke-instance-permissions', '撤销角色实例权限')]
class RevokeInstancePermissionsFromRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*.slug' => 'required|string',
            'permissions.*.resource_type' => 'required|string',
            'permissions.*.resource_id' => 'required|integer',
        ];
    }

    /**
     * 执行撤销（批量）
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');

        $role = $roleModel::findOrFail($this->context->id());
        $permissions = $this->context->data('permissions');

        $permissionIds = [];
        $notFoundPermissions = [];

        foreach ($permissions as $item) {
            $permission = $permissionModel::where('slug', $item['slug'])
                ->where('resource_type', $item['resource_type'])
                ->where('resource_id', $item['resource_id'])
                ->first();

            if ($permission) {
                $permissionIds[] = $permission->id;
            } else {
                $notFoundPermissions[] = "{$item['slug']}|{$item['resource_type']}|{$item['resource_id']}";
            }
        }

        // 如果所有权限都不存在，抛出异常
        if (empty($permissionIds) && !empty($notFoundPermissions)) {
            throw new \Exception(
                '以下实例权限不存在，无法撤销：' . implode(', ', $notFoundPermissions)
            );
        }

        if (!empty($permissionIds)) {
            $role->permissions()->detach($permissionIds);
        }

        // 清除关联用户的缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });

        return $role->load(['permissions', 'users']);
    }
}

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
 *         ['resource' => 'article', 'resource_id' => 123, 'action' => 'update'],
 *         ['resource' => 'article', 'resource_id' => 124, 'action' => 'update'],
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
            'permissions.*.resource' => 'required|string',
            'permissions.*.resource_id' => 'required|integer',
            'permissions.*.action' => 'required|string',
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

        foreach ($permissions as $item) {
            $slug = $item['resource'].':'.$item['action'];

            $permission = $permissionModel::where('slug', $slug)
                ->where('resource_type', $item['resource'])
                ->where('resource_id', $item['resource_id'])
                ->first();

            if ($permission) {
                $permissionIds[] = $permission->id;
            }
        }

        if (! empty($permissionIds)) {
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

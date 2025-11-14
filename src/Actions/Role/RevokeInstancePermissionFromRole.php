<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 撤销角色的实例权限
 * 
 * @example
 * RevokeInstancePermissionFromRole::handle([
 *     'role_id' => 1,
 *     'permission_slug' => 'report:view',
 *     'resource_type' => 'App\Models\Report',
 *     'resource_id' => 123,
 * ]);
 */
#[Permission('role:revoke-instance-permissions', '撤销角色实例权限')]
class RevokeInstancePermissionFromRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $roleTable = config('rbac.tables.roles');
        
        return [
            'role_id' => "required|exists:{$roleTable},id",
            'permission_slug' => 'required|string',
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
        ];
    }

    /**
     * 执行撤销
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');
        
        $role = $roleModel::findOrFail($this->context->data('role_id'));
        
        // 查找实例权限
        $permission = $permissionModel::where('slug', $this->context->data('permission_slug'))
            ->where('resource_type', $this->context->data('resource_type'))
            ->where('resource_id', $this->context->data('resource_id'))
            ->first();

        if ($permission) {
            $role->permissions()->detach($permission->id);
        }

        return $role->load('permissions');
    }
}

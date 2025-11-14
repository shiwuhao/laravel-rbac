<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 撤销角色权限
 */
#[Permission('role:revoke-permission', '撤销角色权限')]
class RevokePermissionFromRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $permissionTable = config('rbac.tables.permissions');
        
        return [
            'permission_id' => "required|exists:{$permissionTable},id",
        ];
    }

    /**
     * 执行撤销
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $role = $roleModel::findOrFail($this->context->id());
        
        $permissionId = $this->context->data('permission_id');
        
        $role->permissions()->detach($permissionId);
        
        // 清除关联用户的权限缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });
        
        return $role->load(['permissions', 'users']);
    }
}

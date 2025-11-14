<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 同步角色权限（替换）
 */
#[Permission('role:sync-permissions', '同步角色权限')]
class SyncPermissionsToRole extends BaseAction
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
     * 执行同步
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');
        
        $role = $roleModel::findOrFail($this->context->id());
        
        $permissionIds = array_values(array_unique(array_map('intval', $this->context->data('permission_ids'))));
        
        $permissions = $permissionModel::whereIn('id', $permissionIds)
            ->where('guard_name', $role->guard_name)
            ->get();
        
        if ($permissions->count() !== count($permissionIds)) {
            throw new \Exception('部分权限不存在或守护名称不匹配');
        }
        
        $role->permissions()->sync($permissionIds);
        
        // 清除关联用户的权限缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });
        
        return $role->load(['permissions', 'users']);
    }
}

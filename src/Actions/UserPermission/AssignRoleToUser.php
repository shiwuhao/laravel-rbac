<?php

namespace Rbac\Actions\UserPermission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user-permission:assign-role', '分配用户角色')]
class AssignRoleToUser extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'role_id' => 'required|integer|exists:rbac_roles,id',
        ];
    }

    /**
     * 分配角色给用户（仅添加，不移除现有角色）
     *
     * @return mixed
     * 
     * @example
     * // 静态调用方式
     * AssignRoleToUser::handle(['role_id' => 1], $userId);
     * 
     * // 实例调用方式
     * $action = new AssignRoleToUser();
     * $action(['role_id' => 1], $userId);
     */
    protected function execute(): mixed
    {
        $userId = $this->context->id();
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($userId);
        
        $roleId = $this->context->data('role_id');
        $role = Role::findOrFail($roleId);

        // 只添加新角色，不移除已存在的角色
        $user->roles()->syncWithoutDetaching([$role->id]);
        
        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load('roles');
    }
}
<?php

namespace Rbac\Actions\UserPermission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user-permission:sync-roles', '同步角色给用户')]
class SyncRolesToUser extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:rbac_roles,id',
        ];
    }

    /**
     * 同步用户角色（完全替换现有角色）
     *
     * @return mixed
     * @throws \Exception
     * 
     * @example
     * // 静态调用方式
     * SyncRolesToUser::handle(['role_ids' => [1, 2, 3]], $userId);
     * 
     * // 实例调用方式
     * $action = new SyncRolesToUser();
     * $action(['role_ids' => [1, 2, 3]], $userId);
     */
    protected function execute(): mixed
    {
        $userId = $this->context->id();
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($userId);

        $roleIds = $this->context->data('role_ids');

        // 验证角色是否都存在
        $roles = Role::whereIn('id', $roleIds)->get();

        if ($roles->count() !== count($roleIds)) {
            throw new \Exception('部分角色不存在');
        }

        // 完全替换用户的角色集合
        $user->roles()->sync($roleIds);

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load('roles');
    }
}
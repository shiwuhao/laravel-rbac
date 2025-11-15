<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 同步用户直接权限（替换）
 *
 * @example
 * SyncPermissionsToUser::handle([
 *     'permission_ids' => [1, 2, 3],
 * ], $userId);
 */
#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:sync-permissions', '同步用户权限')]
class SyncPermissionsToUser extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
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
     * 同步用户直接权限
     *
     * @throws \Exception
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $permissionModel = config('rbac.models.permission');

        $user = $userModel::findOrFail($this->context->id());

        $permissionIds = array_values(array_unique(array_map('intval', $this->context->data('permission_ids'))));

        $permissions = $permissionModel::whereIn('id', $permissionIds)->get();

        if ($permissions->count() !== count($permissionIds)) {
            throw new \Exception('部分权限不存在');
        }

        $user->directPermissions()->sync($permissionIds);

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

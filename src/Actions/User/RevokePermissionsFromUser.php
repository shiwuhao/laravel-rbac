<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 撤销用户直接权限（批量）
 *
 * @example
 * RevokePermissionsFromUser::handle([
 *     'permission_ids' => [1, 2, 3],
 * ], $userId);
 */
#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:revoke-permissions', '从用户撤销权限')]
class RevokePermissionsFromUser extends BaseAction
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
     * 撤销用户直接权限（批量）
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($this->context->id());

        $permissionIds = array_values(array_unique(array_map('intval', $this->context->data('permission_ids'))));

        $user->directPermissions()->detach($permissionIds);

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

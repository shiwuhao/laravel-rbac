<?php

namespace Rbac\Actions\User;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 撤销用户角色（批量）
 *
 * @example
 * RevokeRolesFromUser::handle([
 *     'role_ids' => [1, 2, 3],
 * ], $userId);
 */
#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:revoke-roles', '从用户撤销角色')]
class RevokeRolesFromUser extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $roleTable = config('rbac.tables.roles');

        return [
            'role_ids' => 'required|array',
            'role_ids.*' => "exists:{$roleTable},id",
        ];
    }

    /**
     * 撤销用户角色（批量）
     */
    protected function execute(): \Illuminate\Database\Eloquent\Model
    {
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($this->context->id());

        $roleIds = array_values(array_unique(array_map('intval', $this->context->data('role_ids'))));

        $user->roles()->detach($roleIds);

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

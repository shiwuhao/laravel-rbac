<?php

namespace Rbac\Actions\UserPermission;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:revoke-roles', '从用户撤销角色')]
class RevokeRoleFromUser extends BaseAction
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
     *
     * @return Model
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($this->context->id());

        $roleIds = array_values(array_unique(array_map('intval', $this->context->data('role_ids'))));

        $user->roles()->detach($roleIds);

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'permissions']);
    }
}

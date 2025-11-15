<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 分配角色给用户（批量）
 *
 * @example
 * AssignRolesToUser::handle([
 *     'role_ids' => [1, 2, 3],
 *     'replace' => false,
 * ], $userId);
 */
#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:assign-roles', '分配角色给用户')]
class AssignRolesToUser extends BaseAction
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
            'replace' => 'sometimes|boolean',
        ];
    }

    /**
     * 分配用户角色
     *
     * @throws \Exception
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $roleModel = config('rbac.models.role');

        $user = $userModel::findOrFail($this->context->id());

        $roleIds = array_values(array_unique(array_map('intval', $this->context->data('role_ids'))));
        $replace = $this->context->data('replace', false);

        $roles = $roleModel::whereIn('id', $roleIds)->get();

        if ($roles->count() !== count($roleIds)) {
            throw new \Exception('部分角色不存在');
        }

        if ($replace) {
            $user->roles()->sync($roleIds);
        } else {
            $user->roles()->syncWithoutDetaching($roleIds);
        }

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

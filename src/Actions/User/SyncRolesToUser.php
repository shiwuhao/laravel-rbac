<?php

namespace Rbac\Actions\User;

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
        $roleTable = config('rbac.tables.roles');
        return [
            'role_ids' => 'required|array',
            'role_ids.*' => "exists:{$roleTable},id",
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
        $roleIds = $this->context->data('role_ids');

        return AssignRolesToUser::handle([
            'role_ids' => $roleIds,
            'replace' => true,
        ], $userId);
    }
}
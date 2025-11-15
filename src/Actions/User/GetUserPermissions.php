<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取指定用户的权限信息
 *
 * @example
 * GetUserPermissions::handle([], $userId);
 */
#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:view-permissions', '查看用户权限')]
class GetUserPermissions extends BaseAction
{
    /**
     * 获取指定用户的权限信息
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');

        return $userModel::with(['roles.permissions', 'permissions'])
            ->findOrFail($this->context->id());
    }
}

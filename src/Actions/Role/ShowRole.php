<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:view', '查看角色')]
class ShowRole extends BaseAction
{
    /**
     * 获取角色详情
     *
     * @return Role
     */
    protected function execute(): Role
    {
        return Role::findOrFail($this->context->id());
    }
}

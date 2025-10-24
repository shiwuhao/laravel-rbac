<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:view', '查看权限')]
class ShowPermission extends BaseAction
{
    /**
     * 获取权限详情
     *
     * @return Permission
     */
    protected function execute(): Permission
    {
        return Permission::findOrFail($this->context->id());
    }
}

<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:delete', '删除权限')]
class DeletePermission extends BaseAction
{
    /**
     * 删除权限
     *
     * @return array{deleted: bool}
     */
    protected function execute(): array
    {
        $permission = Permission::findOrFail($this->context->id());
        $permission->delete();

        return ['deleted' => true];
    }
}

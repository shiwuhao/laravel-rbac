<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:delete', '删除角色')]
class DeleteRole extends BaseAction
{
    /**
     * 删除角色
     *
     * @return array{deleted: bool}
     */
    protected function execute(): array
    {
        $role = Role::findOrFail($this->context->id());
        $role->forceDelete();

        return ['deleted' => true];
    }
}

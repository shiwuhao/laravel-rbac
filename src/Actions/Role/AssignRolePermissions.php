<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;
use Rbac\Models\Permission;

#[PermissionGroup('role:*', '角色管理')]
#[\Rbac\Attributes\Permission('role:assign', '分配角色权限')]
class AssignRolePermissions extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:rbac_permissions,id',
            'replace' => 'sometimes|boolean',
        ];
    }

    /**
     * 分配角色权限
     *
     * @return Role
     * @throws \Exception
     */
    protected function execute(): Role
    {
        $roleId = $this->context->id();
        $role = Role::findOrFail($roleId);
        
        $permissionIds = $this->context->data('permission_ids');
        $replace = $this->context->data('replace', false);

        $permissions = Permission::whereIn('id', $permissionIds)
            ->where('guard_name', $role->guard_name)
            ->get();

        if ($permissions->count() !== count($permissionIds)) {
            throw new \Exception('部分权限不存在或守护名称不匹配');
        }

        if ($replace) {
            $role->permissions()->sync($permissionIds);
        } else {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        return $role->load('permissions');
    }
}
<?php

namespace Rbac\Actions\Permission;

use Illuminate\Validation\Rule;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:update', '更新权限')]
class UpdatePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $id = $this->context?->id();
        
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('rbac_permissions', 'slug')->ignore($id),
            ],
            'description' => 'nullable|string|max:500',
            'resource_type' => 'sometimes|string|max:100',
            'resource_id' => 'nullable|integer|min:1',
            'operation' => 'sometimes|string|max:50',
            'guard_name' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * 更新权限
     *
     * @return Permission
     */
    protected function execute(): Permission
    {
        $permission = Permission::findOrFail($this->context->id());

        $permission->update($this->context->only([
            'name',
            'slug',
            'description',
            'resource_type',
            'resource_id',
            'operation',
            'guard_name',
            'metadata',
        ]));

        return $permission;
    }
}

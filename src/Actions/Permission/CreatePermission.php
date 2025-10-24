<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:create', '创建权限')]
class CreatePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $table = config('rbac.tables.permissions', 'permissions');
        
        return [
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:{$table},slug",
            'description' => 'nullable|string|max:500',
            'resource' => 'nullable|string|max:100',
            'action' => 'nullable|string|max:50',
            'guard_name' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * 创建权限
     *
     * @return Permission
     */
    protected function execute(): Permission
    {
        return Permission::create([
            'name' => $this->context->data('name'),
            'slug' => $this->context->data('slug'),
            'description' => $this->context->data('description'),
            'resource' => $this->context->data('resource'),
            'action' => $this->context->data('action'),
            'guard_name' => $this->context->data('guard_name', 'web'),
            'metadata' => $this->context->data('metadata'),
        ]);
    }
}

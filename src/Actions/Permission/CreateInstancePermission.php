<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:create-instance', '创建实例权限')]
class CreateInstancePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'resource_type' => 'required|string|max:100',
            'resource_id' => 'required|integer|min:1',
            'operation' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'guard_name' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * 创建实例权限
     *
     * @return Permission
     */
    protected function execute(): Permission
    {
        $resourceType = $this->context->data('resource_type');
        $resourceId = $this->context->data('resource_id');
        $operation = $this->context->data('operation');
        
        $slug = Permission::generateSlug($resourceType, $operation, $resourceId);
        $name = $this->context->data('name') 
            ?? Permission::generateName($resourceType, $operation, $resourceId);

        return Permission::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $this->context->data('description'),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'operation' => $operation,
            'guard_name' => $this->context->data('guard_name', 'web'),
            'metadata' => $this->context->data('metadata'),
        ]);
    }
}

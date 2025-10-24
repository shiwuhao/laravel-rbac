<?php

namespace Rbac\Actions\Permission;

use Illuminate\Support\Collection;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:batch-create', '批量创建权限')]
class BatchCreatePermissions extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'resource_type' => 'required|string|max:100',
            'operations' => 'required|array|min:1',
            'operations.*' => 'string|max:50',
            'guard_name' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 批量创建权限
     *
     * @return Collection<int, Permission>
     */
    protected function execute(): Collection
    {
        $permissions = collect();
        $resourceType = $this->context->data('resource_type');
        $operations = $this->context->data('operations');
        $guardName = $this->context->data('guard_name', 'web');

        foreach ($operations as $operation) {
            $slug = Permission::generateSlug($resourceType, $operation);
            $name = Permission::generateName($resourceType, $operation);

            $permission = Permission::create([
                'name' => $name,
                'slug' => $slug,
                'resource_type' => $resourceType,
                'operation' => $operation,
                'guard_name' => $guardName,
            ]);

            $permissions->push($permission);
        }

        return $permissions;
    }
}

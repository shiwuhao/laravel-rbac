<?php

namespace Rbac\Actions\Permission;

use Illuminate\Support\Collection;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;

/**
 * 批量创建权限
 *
 * @example
 * BatchCreatePermissions::handle([
 *     'resource' => 'article',
 *     'actions' => ['view', 'create', 'update', 'delete'],
 *     'guard_name' => 'web',
 * ]);
 */
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
            'resource' => 'required|string|max:100',
            'actions' => 'required|array|min:1',
            'actions.*' => 'string|max:50',
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
        $permissionModel = config('rbac.models.permission');
        $permissions = collect();
        $resource = $this->context->data('resource');
        $actions = $this->context->data('actions');
        $guardName = $this->context->data('guard_name', 'web');

        foreach ($actions as $action) {
            $slug = $permissionModel::generateSlug($resource, $action);
            $name = $permissionModel::generateName($resource, $action);

            $permission = $permissionModel::create([
                'name' => $name,
                'slug' => $slug,
                'resource' => $resource,
                'action' => $action,
                'guard_name' => $guardName,
            ]);

            $permissions->push($permission);
        }

        return $permissions;
    }
}

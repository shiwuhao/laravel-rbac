<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Permission;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:find-or-create', '查找或创建权限')]
class FindOrCreatePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'slug' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'resource' => 'required|string|max:100',
            'action' => 'required|string|in:view,create,update,delete,export,import,manage',
            'guard' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 查找或创建权限
     *
     * @return Permission
     */
    protected function execute(): Permission
    {
        $slug = $this->context->data('slug');
        $name = $this->context->data('name');
        $resource = $this->context->data('resource');
        $action = $this->context->data('action');
        $guard = $this->context->data('guard', 'web');

        $actionType = ActionType::from($action);
        $guardType = GuardType::from($guard);

        return Permission::firstOrCreate(
            [
                'slug' => $slug,
                'guard_name' => $guardType->value,
            ],
            [
                'name' => $name,
                'resource' => $resource,
                'action' => $actionType->value,
            ]
        );
    }
}
<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:create', '创建角色')]
class CreateRole extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:roles,slug',
            'description' => 'nullable|string',
            'guard_name' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 创建角色
     *
     * @return Role
     */
    protected function execute(): Role
    {
        return Role::create([
            'name' => $this->context->data('name'),
            'slug' => $this->context->data('slug'),
            'description' => $this->context->data('description', ''),
            'guard_name' => $this->context->data('guard_name', 'web'),
        ]);
    }
}

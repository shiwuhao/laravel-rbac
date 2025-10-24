<?php

namespace Rbac\Actions\Role;

use Illuminate\Validation\Rule;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:update', '更新角色')]
class UpdateRole extends BaseAction
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
            'name' => 'sometimes|string|max:100',
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('roles', 'slug')->ignore($id),
            ],
            'description' => 'nullable|string',
            'guard_name' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 更新角色
     *
     * @return Role
     */
    protected function execute(): Role
    {
        $role = Role::findOrFail($this->context->id());

        $role->update($this->context->only([
            'name',
            'slug',
            'description',
            'guard_name',
        ]));

        return $role;
    }
}

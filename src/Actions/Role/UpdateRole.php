<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\RoleContract;

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
        $roleTable = config('rbac.tables.roles');
        
        return [
            'name' => 'sometimes|string|max:100',
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique($roleTable, 'slug')->ignore($id),
            ],
            'description' => 'nullable|string',
            'guard_name' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 更新角色
     *
     * @return RoleContract&Model 返回配置的角色模型实例，默认为 \Rbac\Models\Role
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $role = $roleModel::findOrFail($this->context->id());

        $role->update($this->context->only([
            'name',
            'slug',
            'description',
            'guard_name',
        ]));

        return $role;
    }
}

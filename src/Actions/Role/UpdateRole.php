<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\RoleContract;

/**
 * 更新角色
 *
 * @example
 * UpdateRole::handle([
 *     'name' => '高级管理员',
 *     'description' => '拥有部分管理权限',
 * ], $roleId);
 */
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
        $roleTable = config('rbac.tables.roles');
        $roleId = $this->routeParams[0] ?? null;

        return [
            'name' => 'sometimes|string|max:100',
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique($roleTable, 'slug')->ignore($roleId),
            ],
            'description' => 'nullable|string',
            'guard_name' => 'sometimes|string|max:50',
            'enabled' => 'sometimes|boolean',
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
            'enabled',
        ]));

        return $role;
    }
}

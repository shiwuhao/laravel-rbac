<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\RoleContract;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:create', '创建角色', description: '创建新的系统角色')]
class CreateRole extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $roleTable = config('rbac.tables.roles');
        return [
            'name' => 'required|string|max:100',
            'slug' => "required|string|max:100|unique:{$roleTable},slug",
            'description' => 'nullable|string',
            'guard_name' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 创建角色
     *
     * @return RoleContract&Model 返回配置的角色模型实例，默认为 \Rbac\Models\Role
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        
        return $roleModel::create([
            'name' => $this->context->data('name'),
            'slug' => $this->context->data('slug'),
            'description' => $this->context->data('description', ''),
            'guard_name' => $this->context->data('guard_name', 'web'),
        ]);
    }
}

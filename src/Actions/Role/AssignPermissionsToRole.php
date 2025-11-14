<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\RoleContract;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:assign-permissions', '分配权限给角色')]
class AssignPermissionsToRole extends BaseAction
{
    /**
     * 验证规则
     *·
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $permissionTable = config('rbac.tables.permissions');
        return [
            'permission_ids' => 'required|array',
            'permission_ids.*' => "exists:{$permissionTable},id",
            'replace' => 'sometimes|boolean',
        ];
    }

    /**
     * 分配角色权限
     *
     * @return RoleContract&Model 返回配置的角色模型实例，含权限与用户
     * @throws \Exception
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');

        $roleId = $this->context->id();
        $role = $roleModel::findOrFail($roleId);

        $permissionIds = $this->context->data('permission_ids');
        $replace = $this->context->data('replace', false);

        $permissions = $permissionModel::whereIn('id', $permissionIds)
            ->where('guard_name', $role->guard_name)
            ->get();

        if ($permissions->count() !== count($permissionIds)) {
            throw new \Exception('部分权限不存在或守护名称不匹配');
        }

        if ($replace) {
            $role->permissions()->sync($permissionIds);
        } else {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        // 清除关联用户的权限缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });

        return $role->load(['permissions', 'users']);
    }
}
<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:assign-permissions', '分配权限给用户')]
class AssignPermissionsToUser extends BaseAction
{
    /**
     * 验证规则
     *
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
     * 分配用户直接权限
     *
     * @return Model
     * @throws \Exception
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $permissionModel = config('rbac.models.permission');
        
        $user = $userModel::findOrFail($this->context->id());

        $permissionIds = array_values(array_unique(array_map('intval', $this->context->data('permission_ids'))));
        $replace = $this->context->data('replace', false);

        $permissions = $permissionModel::whereIn('id', $permissionIds)->get();

        if ($permissions->count() !== count($permissionIds)) {
            throw new \Exception('部分权限不存在');
        }

        if ($replace) {
            $user->directPermissions()->sync($permissionIds);
        } else {
            $user->directPermissions()->syncWithoutDetaching($permissionIds);
        }

        // 清除用户缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

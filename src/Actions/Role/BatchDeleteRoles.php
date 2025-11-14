<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:batch-delete', '批量删除角色')]
class BatchDeleteRoles extends BaseAction
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
            'role_ids' => 'required|array',
            'role_ids.*' => "exists:{$roleTable},id",
            'force' => 'sometimes|boolean',
        ];
    }

    /**
     * 批量删除角色
     *
     * @return array{deleted: int, errors: array}
     */
    protected function execute(): array
    {
        $roleModel = config('rbac.models.role');
        $roleIds = $this->context->data('role_ids');
        $force = $this->context->data('force', false);

        $deleted = 0;
        $errors = [];

        foreach ($roleIds as $roleId) {
            try {
                $role = $roleModel::findOrFail($roleId);
                
                $usersCount = $role->users()->count();
                
                if ($usersCount > 0 && !$force) {
                    $errors[] = [
                        'id' => $roleId,
                        'name' => $role->name,
                        'message' => "角色正被 {$usersCount} 个用户使用，请先解除关联或使用强制删除"
                    ];
                    continue;
                }

                // 清除关联用户的权限缓存
                $role->users()->each(function ($user) {
                    if (method_exists($user, 'forgetCachedPermissions')) {
                        $user->forgetCachedPermissions();
                    }
                });
                
                // 解除所有关联
                $role->permissions()->detach();
                $role->users()->detach();
                
                $role->forceDelete();
                $deleted++;
                
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $roleId,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors
        ];
    }
}

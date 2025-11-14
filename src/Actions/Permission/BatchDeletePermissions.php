<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:batch-delete', '批量删除权限')]
class BatchDeletePermissions extends BaseAction
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
            'force' => 'sometimes|boolean',
        ];
    }

    /**
     * 批量删除权限
     *
     * @return array{deleted: int, errors: array}
     */
    protected function execute(): array
    {
        $permissionModel = config('rbac.models.permission');
        $permissionIds = $this->context->data('permission_ids');
        $force = $this->context->data('force', false);

        $deleted = 0;
        $errors = [];

        foreach ($permissionIds as $permissionId) {
            try {
                $permission = $permissionModel::findOrFail($permissionId);
                
                $rolesCount = $permission->roles()->count();
                
                if ($rolesCount > 0 && !$force) {
                    $errors[] = [
                        'id' => $permissionId,
                        'name' => $permission->name,
                        'message' => "权限正被 {$rolesCount} 个角色使用，请先解除关联或使用强制删除"
                    ];
                    continue;
                }
                
                // 清除关联用户的权限缓存
                $permission->roles()->each(function ($role) {
                    $role->users()->each(function ($user) {
                        if (method_exists($user, 'forgetCachedPermissions')) {
                            $user->forgetCachedPermissions();
                        }
                    });
                });
                
                // 解除所有关联
                $permission->roles()->detach();
                $permission->users()->detach();
                $permission->dataScopes()->detach();
                
                $permission->delete();
                $deleted++;
                
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $permissionId,
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

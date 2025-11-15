<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;

/**
 * 删除权限
 *
 * @example
 * DeletePermission::handle([
 *     'force' => true,
 * ], $permissionId);
 */
#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:delete', '删除权限')]
class DeletePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'force' => 'sometimes|boolean',
        ];
    }

    /**
     * 删除权限
     *
     * @return array{deleted: bool, detached_roles: int}
     *
     * @throws \Exception
     */
    protected function execute(): array
    {
        $permissionModel = config('rbac.models.permission');
        $permission = $permissionModel::findOrFail($this->context->id());

        // 检查是否被角色使用
        $rolesCount = $permission->roles()->count();
        $force = $this->context->data('force', false);

        if ($rolesCount > 0 && ! $force) {
            throw new \Exception("权限正被 {$rolesCount} 个角色使用，请先解除关联或使用强制删除");
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

        return ['deleted' => true, 'detached_roles' => $rolesCount];
    }
}

<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;

/**
 * 撤销用户的实例权限（批量）
 *
 * @example
 * RevokeInstancePermissionsFromUser::handle([
 *     'permissions' => [
 *         ['slug' => 'report:access', 'resource_type' => 'report', 'resource_id' => 123],
 *         ['slug' => 'report:access', 'resource_type' => 'report', 'resource_id' => 124],
 *     ],
 * ], $userId);
 */
#[Permission('user:revoke-instance-permissions', '撤销用户实例权限')]
class RevokeInstancePermissionsFromUser extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*.slug' => 'required|string',
            'permissions.*.resource_type' => 'required|string',
            'permissions.*.resource_id' => 'required|integer',
        ];
    }

    /**
     * 执行撤销（批量）
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $permissionModel = config('rbac.models.permission');

        $user = $userModel::findOrFail($this->context->id());
        $permissions = $this->context->data('permissions');

        $permissionIds = [];
        $notFoundPermissions = [];

        foreach ($permissions as $item) {
            $permission = $permissionModel::where('slug', $item['slug'])
                ->where('resource_type', $item['resource_type'])
                ->where('resource_id', $item['resource_id'])
                ->first();

            if ($permission) {
                $permissionIds[] = $permission->id;
            } else {
                $notFoundPermissions[] = "{$item['slug']}|{$item['resource_type']}|{$item['resource_id']}";
            }
        }

        // 如果所有权限都不存在，抛出异常
        if (empty($permissionIds) && !empty($notFoundPermissions)) {
            throw new \Exception(
                '以下实例权限不存在，无法撤销：' . implode(', ', $notFoundPermissions)
            );
        }

        if (!empty($permissionIds)) {
            $user->directPermissions()->detach($permissionIds);
        }

        // 清理缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

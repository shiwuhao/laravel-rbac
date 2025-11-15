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
 *         ['resource' => 'article', 'resource_id' => 123, 'action' => 'update'],
 *         ['resource' => 'article', 'resource_id' => 124, 'action' => 'update'],
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
            'permissions.*.resource' => 'required|string',
            'permissions.*.resource_id' => 'required|integer',
            'permissions.*.action' => 'required|string',
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

        foreach ($permissions as $item) {
            $slug = $item['resource'].':'.$item['action'];

            $permission = $permissionModel::where('slug', $slug)
                ->where('resource_type', $item['resource'])
                ->where('resource_id', $item['resource_id'])
                ->first();

            if ($permission) {
                $permissionIds[] = $permission->id;
            }
        }

        if (! empty($permissionIds)) {
            $user->directPermissions()->detach($permissionIds);
        }

        // 清理缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load(['roles', 'directPermissions']);
    }
}

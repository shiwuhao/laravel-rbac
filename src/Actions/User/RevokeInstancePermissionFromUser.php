<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;

/**
 * 撤销用户的实例权限
 * 
 * @example
 * RevokeInstancePermissionFromUser::handle([
 *     'user_id' => 1,
 *     'permission_slug' => 'report:view',
 *     'resource_type' => 'App\Models\Report',
 *     'resource_id' => 123,
 * ]);
 */
#[Permission('user:revoke-instance-permissions', '撤销用户实例权限')]
class RevokeInstancePermissionFromUser extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $userTable = config('rbac.tables.users', 'users');
        
        return [
            'user_id' => "required|exists:{$userTable},id",
            'permission_slug' => 'required|string',
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
        ];
    }

    /**
     * 执行撤销
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $permissionModel = config('rbac.models.permission');
        
        $user = $userModel::findOrFail($this->context->data('user_id'));
        
        // 查找实例权限
        $permission = $permissionModel::where('slug', $this->context->data('permission_slug'))
            ->where('resource_type', $this->context->data('resource_type'))
            ->where('resource_id', $this->context->data('resource_id'))
            ->first();

        if ($permission) {
            $user->directPermissions()->detach($permission->id);
        }

        // 清理缓存
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }

        return $user->load('directPermissions');
    }
}

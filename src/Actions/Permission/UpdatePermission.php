<?php

namespace Rbac\Actions\Permission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\PermissionContract;

/**
 * 更新权限
 *
 * @example
 * UpdatePermission::handle([
 *     'name' => '编辑文章',
 *     'description' => '允许编辑文章内容',
 * ], $permissionId);
 */
#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:update', '更新权限')]
class UpdatePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $id = $this->routeParams[0] ?? null;
        $permissionTable = config('rbac.tables.permissions');

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique($permissionTable, 'slug')->ignore($id),
            ],
            'description' => 'nullable|string|max:500',
            'resource' => 'sometimes|string|max:100',
            'action' => 'sometimes|string|max:50',
            'guard_name' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * 更新权限
     *
     * @return PermissionContract&Model 返回配置的权限模型实例，默认为 \Rbac\Models\Permission
     */
    protected function execute(): PermissionContract&Model
    {
        $permissionModel = config('rbac.models.permission');
        $permission = $permissionModel::findOrFail($this->context->id());

        $permission->update($this->context->only([
            'name',
            'slug',
            'description',
            'resource',
            'action',
            'guard_name',
            'metadata',
        ]));

        // 清除关联角色与用户的缓存
        $permission->roles()->each(function ($role) {
            $role->users()->each(function ($user) {
                if (method_exists($user, 'forgetCachedPermissions')) {
                    $user->forgetCachedPermissions();
                }
            });
        });

        return $permission;
    }
}

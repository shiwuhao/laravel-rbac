<?php

namespace Rbac\Actions\Permission;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\PermissionContract;

/**
 * 创建权限
 *
 * @example
 * CreatePermission::handle([
 *     'name' => '查看文章',
 *     'slug' => 'article:view',
 *     'description' => '允许查看文章列表',
 *     'resource' => 'article',
 *     'action' => 'view',
 *     'guard_name' => 'web',
 *     'metadata' => ['priority' => 1],
 * ]);
 */
#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:create', '创建权限')]
class CreatePermission extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $table = config('rbac.tables.permissions', 'permissions');

        return [
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:{$table},slug",
            'description' => 'nullable|string|max:500',
            'resource' => 'nullable|string|max:100',
            'action' => 'nullable|string|max:50',
            'guard_name' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * 创建权限
     *
     * @return PermissionContract&Model 返回配置的权限模型实例，默认为 \Rbac\Models\Permission
     */
    protected function execute(): PermissionContract&Model
    {
        $permissionModel = config('rbac.models.permission');

        return $permissionModel::create([
            'name' => $this->context->data('name'),
            'slug' => $this->context->data('slug'),
            'description' => $this->context->data('description'),
            'resource' => $this->context->data('resource'),
            'action' => $this->context->data('action'),
            'guard_name' => $this->context->data('guard_name', 'web'),
            'metadata' => $this->context->data('metadata'),
        ]);
    }
}

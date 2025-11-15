<?php

namespace Rbac\Actions\Permission;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Contracts\PermissionContract;

/**
 * 创建实例级权限
 *
 * 用于为具体模型实例创建权限，如：
 * - 报表权限：report:view#123（查看报表#123）
 * - 菜单权限：menu:access#456（访问菜单#456）
 *
 * @example
 * CreateInstancePermission::handle([
 *     'name' => '查看销售报表',
 *     'slug' => 'report:view:sales-2024',
 *     'resource' => 'report',
 *     'action' => 'view',
 *     'resource_type' => 'App\Models\Report',
 *     'resource_id' => 123,
 * ]);
 */
#[PermissionAttribute('permission:create-instance', '创建实例权限')]
class CreateInstancePermission extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $table = config('rbac.tables.permissions', 'permissions');

        return [
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:{$table},slug",
            'description' => 'nullable|string|max:500',
            'resource' => 'required|string|max:100',
            'action' => 'required|string|max:50',
            'resource_type' => 'required|string|max:255',  // 模型类名
            'resource_id' => 'required|integer',           // 实例ID
            'guard_name' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * 创建实例权限
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
            'resource_type' => $this->context->data('resource_type'),
            'resource_id' => $this->context->data('resource_id'),
            'guard_name' => $this->context->data('guard_name', 'web'),
            'metadata' => $this->context->data('metadata'),
        ]);
    }
}

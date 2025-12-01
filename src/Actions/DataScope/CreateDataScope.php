<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\DataScopeContract;

/**
 * 创建数据范围
 *
 * @example
 * CreateDataScope::handle([
 *     'name' => '部门数据',
 *     'slug' => 'department',
 *     'description' => '只能查看本部门数据',
 *     'type' => 'department',
 *     'config' => ['field' => 'department_id'],
 * ]);
 */
#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:create', '创建数据范围')]
class CreateDataScope extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $dataScopeTable = config('rbac.tables.data_scopes');

        return [
            'name' => 'required|string|max:255',
            'slug' => "sometimes|string|max:100|unique:{$dataScopeTable},slug",
            'description' => 'nullable|string|max:500',
            'type' => 'required|string|in:all,organization,department,personal,custom',
            'config' => 'nullable|array',
        ];
    }

    /**
     * 创建数据范围
     *
     * @return DataScopeContract&Model 返回配置的数据范围模型实例，默认为 \Rbac\Models\DataScope
     */
    protected function execute(): DataScopeContract&Model
    {
        $dataScopeModel = config('rbac.models.data_scope');

        return $dataScopeModel::create([
            'name' => $this->context->data('name'),
            'slug' => $this->context->data('slug'),
            'description' => $this->context->data('description'),
            'type' => $this->context->data('type'),
            'config' => $this->context->data('config'),
        ]);
    }
}

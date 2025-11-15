<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\DataScopeContract;

/**
 * 更新数据范围
 *
 * @example
 * UpdateDataScope::handle([
 *     'name' => '部门及下级数据',
 *     'type' => 'department_and_sub',
 * ], $dataScopeId);
 */
#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:update', '更新数据范围')]
class UpdateDataScope extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'sometimes|string|in:all,custom,department,department_and_sub,only_self',
            'config' => 'nullable|array',
        ];
    }

    /**
     * 更新数据范围
     *
     * @return DataScopeContract&Model 返回配置的数据范围模型实例，默认为 \Rbac\Models\DataScope
     */
    protected function execute(): DataScopeContract&Model
    {
        $dataScopeModel = config('rbac.models.data_scope');
        $dataScope = $dataScopeModel::findOrFail($this->context->id());

        $dataScope->update($this->context->only([
            'name',
            'description',
            'type',
            'config',
        ]));

        return $dataScope;
    }
}

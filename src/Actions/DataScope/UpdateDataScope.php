<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Enum;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\DataScopeContract;
use Rbac\Enums\DataScopeType;

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
        $dataScopeTable = config('rbac.tables.data_scopes');
        $dataScopeId = $this->routeParams[0] ?? null;

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => "sometimes|string|max:100|unique:{$dataScopeTable},slug,{$dataScopeId}",
            'description' => 'nullable|string|max:500',
            'type' => ['sometimes', new Enum(DataScopeType::class)],
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
            'slug',
            'description',
            'type',
            'config',
        ]));

        return $dataScope;
    }
}

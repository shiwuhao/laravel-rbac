<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Validation\Rule;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\DataScope;

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
            'rules' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * 更新数据范围
     *
     * @return DataScope
     */
    protected function execute(): DataScope
    {
        $dataScope = DataScope::findOrFail($this->context->id());

        $dataScope->update($this->context->only([
            'name',
            'description',
            'type',
            'rules',
            'is_active',
        ]));

        return $dataScope;
    }
}

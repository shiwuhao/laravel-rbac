<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\DataScope;

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
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|string|in:all,custom,department,department_and_sub,only_self',
            'config' => 'nullable|array',
        ];
    }

    /**
     * 创建数据范围
     *
     * @return DataScope
     */
    protected function execute(): DataScope
    {
        return DataScope::create([
            'name' => $this->context->data('name'),
            'description' => $this->context->data('description'),
            'type' => $this->context->data('type'),
            'config' => $this->context->data('config'),
        ]);
    }
}

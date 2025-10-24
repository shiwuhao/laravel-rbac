<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\DataScope;

#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:delete', '删除数据范围')]
class DeleteDataScope extends BaseAction
{
    /**
     * 删除数据范围
     *
     * @return array{deleted: bool}
     */
    protected function execute(): array
    {
        $dataScope = DataScope::findOrFail($this->context->id());
        $dataScope->delete();

        return ['deleted' => true];
    }
}

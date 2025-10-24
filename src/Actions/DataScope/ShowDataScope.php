<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\DataScope;

#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:view', '查看数据范围')]
class ShowDataScope extends BaseAction
{
    /**
     * 获取数据范围详情
     *
     * @return DataScope
     */
    protected function execute(): DataScope
    {
        return DataScope::findOrFail($this->context->id());
    }
}

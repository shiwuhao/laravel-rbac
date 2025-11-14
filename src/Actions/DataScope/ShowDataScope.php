<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\DataScopeContract;

#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:view', '查看数据范围')]
class ShowDataScope extends BaseAction
{
    /**
     * 获取数据范围详情
     *
     * @return DataScopeContract&Model 返回配置的数据范围模型实例，含权限与用户数统计
     */
    protected function execute(): DataScopeContract&Model
    {
        $dataScopeModel = config('rbac.models.data_scope');
        
        return $dataScopeModel::withCount(['permissions', 'users'])
            ->with('permissions')
            ->findOrFail($this->context->id());
    }
}

<?php

namespace Rbac\Actions\Permission;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\PermissionContract;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:view', '查看权限')]
class ShowPermission extends BaseAction
{
    /**
     * 获取权限详情
     *
     * @return PermissionContract&Model 返回配置的权限模型实例，含角色与用户数统计
     */
    protected function execute(): PermissionContract&Model
    {
        $permissionModel = config('rbac.models.permission');
        
        return $permissionModel::withCount(['roles', 'users'])
            ->with('roles')
            ->findOrFail($this->context->id());
    }
}

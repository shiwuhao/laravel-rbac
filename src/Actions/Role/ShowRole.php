<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Contracts\RoleContract;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:view', '查看角色')]
class ShowRole extends BaseAction
{
    /**
     * 获取角色详情
     *
     * @return RoleContract&Model 返回配置的角色模型实例，含权限与用户数统计
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        
        return $roleModel::withCount(['permissions', 'users'])
            ->with('permissions')
            ->findOrFail($this->context->id());
    }
}

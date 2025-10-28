<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:get-stats', '获取权限统计信息')]
class GetPermissionStats extends BaseAction
{
    /**
     * 获取权限统计信息
     *
     * @return array
     */
    protected function execute(): array
    {
        return [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'total_data_scopes' => DataScope::count(),
            'permissions_by_resource' => Permission::select('resource')
                ->selectRaw('count(*) as count')
                ->groupBy('resource')
                ->pluck('count', 'resource')
                ->toArray(),
            'permissions_by_action' => Permission::select('action')
                ->selectRaw('count(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }
}
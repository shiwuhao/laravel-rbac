<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 撤销角色数据范围
 */
#[Permission('role:revoke-data-scope', '撤销角色数据范围')]
class RevokeDataScopeFromRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $dataScopeTable = config('rbac.tables.data_scopes');
        
        return [
            'data_scope_id' => "required|exists:{$dataScopeTable},id",
        ];
    }

    /**
     * 执行撤销
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $role = $roleModel::findOrFail($this->context->id());
        
        $dataScopeId = $this->context->data('data_scope_id');
        
        $role->dataScopes()->detach($dataScopeId);
        
        // 清除关联用户的缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });
        
        return $role->load(['dataScopes', 'users']);
    }
}

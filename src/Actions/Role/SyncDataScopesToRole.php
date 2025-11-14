<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 同步角色数据范围（替换）
 */
#[Permission('role:sync-data-scopes', '同步角色数据范围')]
class SyncDataScopesToRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $dataScopeTable = config('rbac.tables.data_scopes');
        
        return [
            'data_scope_ids' => 'required|array',
            'data_scope_ids.*' => "exists:{$dataScopeTable},id",
        ];
    }

    /**
     * 执行同步
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $dataScopeModel = config('rbac.models.data_scope');
        
        $role = $roleModel::findOrFail($this->context->id());
        
        $dataScopeIds = array_values(array_unique(array_map('intval', $this->context->data('data_scope_ids'))));
        
        $dataScopes = $dataScopeModel::whereIn('id', $dataScopeIds)->get();
        
        if ($dataScopes->count() !== count($dataScopeIds)) {
            throw new \Exception('部分数据范围不存在');
        }
        
        $role->dataScopes()->sync($dataScopeIds);
        
        // 清除关联用户的缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });
        
        return $role->load(['dataScopes', 'users']);
    }
}

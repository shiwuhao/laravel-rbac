<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 撤销角色数据范围（批量）
 *
 * @example
 * RevokeDataScopesFromRole::handle([
 *     'data_scope_ids' => [1, 2, 3],
 * ], $roleId);
 */
#[Permission('role:revoke-data-scopes', '撤销角色数据范围')]
class RevokeDataScopesFromRole extends BaseAction
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
     * 执行撤销（批量）
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $role = $roleModel::findOrFail($this->context->id());

        $dataScopeIds = array_values(array_unique(array_map('intval', $this->context->data('data_scope_ids'))));

        $role->dataScopes()->detach($dataScopeIds);

        // 清除关联用户的缓存
        $role->users()->each(function ($user) {
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }
        });

        return $role->load(['dataScopes', 'users']);
    }
}

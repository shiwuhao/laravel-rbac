<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 撤销用户数据范围（批量）
 *
 * @example
 * RevokeDataScopesFromUser::handle([
 *     'data_scope_ids' => [1, 2, 3],
 * ], $userId);
 */
#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:revoke-data-scopes', '从用户撤销数据范围')]
class RevokeDataScopesFromUser extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
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
     * 撤销用户数据范围（批量）
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($this->context->id());

        $dataScopeIds = array_values(array_unique(array_map('intval', $this->context->data('data_scope_ids'))));

        $user->dataScopes()->detach($dataScopeIds);

        return $user->load(['dataScopes']);
    }
}

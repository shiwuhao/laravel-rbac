<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('user-permission:*', '用户权限管理')]
#[Permission('user:assign-data-scopes', '分配数据范围给用户')]
class AssignDataScopesToUser extends BaseAction
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
            'constraint' => 'sometimes|string|nullable',
            'replace' => 'sometimes|boolean',
        ];
    }

    /**
     * 分配用户数据范围
     *
     * @return Model
     * @throws \Exception
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $dataScopeModel = config('rbac.models.data_scope');
        
        $user = $userModel::findOrFail($this->context->id());

        $dataScopeIds = array_values(array_unique(array_map('intval', $this->context->data('data_scope_ids'))));
        $constraint = $this->context->data('constraint', null);
        $replace = $this->context->data('replace', false);

        $dataScopes = $dataScopeModel::whereIn('id', $dataScopeIds)->get();

        if ($dataScopes->count() !== count($dataScopeIds)) {
            throw new \Exception('部分数据范围不存在');
        }

        // 构建同步数据
        $syncData = [];
        foreach ($dataScopeIds as $dataScopeId) {
            $syncData[$dataScopeId] = ['constraint' => $constraint];
        }

        if ($replace) {
            $user->dataScopes()->sync($syncData);
        } else {
            $user->dataScopes()->syncWithoutDetaching($syncData);
        }

        return $user->load(['dataScopes']);
    }
}

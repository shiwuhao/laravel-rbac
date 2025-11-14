<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:batch-delete', '批量删除数据范围')]
class BatchDeleteDataScopes extends BaseAction
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
            'force' => 'sometimes|boolean',
        ];
    }

    /**
     * 批量删除数据范围
     *
     * @return array{deleted: int, errors: array}
     */
    protected function execute(): array
    {
        $dataScopeModel = config('rbac.models.data_scope');
        $dataScopeIds = $this->context->data('data_scope_ids');
        $force = $this->context->data('force', false);

        $deleted = 0;
        $errors = [];

        foreach ($dataScopeIds as $dataScopeId) {
            try {
                $dataScope = $dataScopeModel::findOrFail($dataScopeId);
                
                $permissionsCount = $dataScope->permissions()->count();
                
                if ($permissionsCount > 0 && !$force) {
                    $errors[] = [
                        'id' => $dataScopeId,
                        'name' => $dataScope->name,
                        'message' => "数据范围正被 {$permissionsCount} 个权限使用，请先解除关联或使用强制删除"
                    ];
                    continue;
                }
                
                // 解除所有关联
                $dataScope->permissions()->detach();
                $dataScope->users()->detach();
                
                $dataScope->delete();
                $deleted++;
                
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $dataScopeId,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors
        ];
    }
}

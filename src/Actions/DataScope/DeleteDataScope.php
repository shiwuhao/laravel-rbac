<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:delete', '删除数据范围')]
class DeleteDataScope extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'force' => 'sometimes|boolean',
        ];
    }

    /**
     * 删除数据范围
     *
     * @return array{deleted: bool, detached_permissions: int}
     * @throws \Exception
     */
    protected function execute(): array
    {
        $dataScopeModel = config('rbac.models.data_scope');
        $dataScope = $dataScopeModel::findOrFail($this->context->id());
        
        // 检查是否被权限使用
        $permissionsCount = $dataScope->permissions()->count();
        $force = $this->context->data('force', false);
        
        if ($permissionsCount > 0 && !$force) {
            throw new \Exception("数据范围正被 {$permissionsCount} 个权限使用，请先解除关联或使用强制删除");
        }
        
        // 解除所有关联
        $dataScope->permissions()->detach();
        $dataScope->users()->detach();
        
        $dataScope->delete();

        return ['deleted' => true, 'detached_permissions' => $permissionsCount];
    }
}

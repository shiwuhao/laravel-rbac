<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取数据范围列表（分页）
 *
 * @example
 * ListDataScope::handle([
 *     'keyword' => '部门',
 *     'type' => 'department',
 *     'per_page' => 20,
 * ]);
 */
#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:view', '查看数据范围')]
class ListDataScope extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:15|max:1000',
        ];
    }

    /**
     * 获取数据范围列表
     */
    protected function execute(): LengthAwarePaginator
    {
        $dataScopeModel = config('rbac.models.data_scope');
        $query = $dataScopeModel::query()->withCount(['permissions', 'users']);

        // 应用查询过滤器（应用层通过配置注入搜索逻辑）
        $query = $this->applyQueryFilter($query, $this->context->raw());

        return $query->paginate($this->getPerPage());
    }
}

<?php

namespace Rbac\Actions\Role;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取角色列表
 *
 * @example
 * ListRole::handle([
 *     'keyword' => '管理',
 *     'per_page' => 20,
 * ]);
 */
#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:view', '查看角色')]
class ListRole extends BaseAction
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
     * 获取角色列表
     */
    protected function execute(): LengthAwarePaginator
    {
        $roleModel = config('rbac.models.role');
        $query = $roleModel::query()->withCount(['permissions', 'users']);

        // 应用查询过滤器（应用层通过配置注入搜索逻辑）
        $query = $this->applyQueryFilter($query, $this->context->raw());

        return $query->paginate($this->getPerPage());
    }
}

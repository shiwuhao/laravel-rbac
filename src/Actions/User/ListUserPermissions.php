<?php

namespace Rbac\Actions\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取用户权限列表（分页）
 *
 * @example
 * ListUserPermissions::handle([
 *     'keyword' => 'admin',
 *     'role' => 'manager',
 *     'per_page' => 20,
 * ]);
 */
#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:view-permissions', '查看用户权限')]
class ListUserPermissions extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:15|max:100',
        ];
    }

    /**
     * 获取用户权限列表
     */
    protected function execute(): LengthAwarePaginator
    {
        $userModel = config('rbac.models.user');
        $query = $userModel::query()->with(['roles.permissions', 'directPermissions']);

        // 应用查询过滤器（应用层通过配置注入搜索逻辑）
        $query = $this->applyQueryFilter($query, $this->context->raw());

        return $query->paginate($this->getPerPage());
    }
}

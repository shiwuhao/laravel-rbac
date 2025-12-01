<?php

namespace Rbac\Actions\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取用户权限列表（分页）
 *
 * 返回用户权限概览信息，包括：
 * - 用户基本信息
 * - 角色列表（简要）
 * - 权限统计数据
 * - 直接权限和数据范围统计
 *
 * @example
 * ListUserPermissions::handle([
 *     'keyword' => 'admin',
 *     'role' => 'manager',
 *     'per_page' => 20,
 * ]);
 */
#[PermissionGroup('user:*', '用户管理'), Permission('user:view-permissions', '查看用户权限')]
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

        $query = $userModel::query()
            ->with([
                'roles',
                'directPermissions',
                'directDataScopes'
            ])
            ->withCount([
                'roles',
                'directPermissions',
                'directDataScopes'
            ]);

        // 应用查询过滤器（应用层通过配置注入搜索逻辑）
        $query = $this->applyQueryFilter($query, $this->context->raw());

        $paginator = $query->paginate($this->getPerPage());

        // 转换分页数据为统计格式
        $paginator->getCollection()->transform(function ($user) {
            return $this->formatUserSummary($user);
        });

        return $paginator;
    }

    /**
     * 格式化用户权限概览
     */
    private function formatUserSummary($user): array
    {
        // 计算从角色继承的权限数量
        $rolePermissionsCount = $user->roles
            ->where('enabled', true)
            ->sum(function ($role) {
                return $role->permissions()->count();
            });

        // 计算从角色继承的数据范围数量
        $roleDataScopesCount = $user->roles
            ->where('enabled', true)
            ->sum(function ($role) {
                return $role->dataScopes()->count();
            });

        return [
            'id' => $user->id,
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,

            // 角色简要信息
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'enabled' => $role->enabled,
                ];
            })->values(),

            // 权限统计
            'stats' => [
                'total_roles' => $user->roles_count,
                'enabled_roles' => $user->roles->where('enabled', true)->count(),
                'disabled_roles' => $user->roles->where('enabled', false)->count(),

                // 权限统计
                'total_permissions' => $rolePermissionsCount + $user->direct_permissions_count,
                'role_permissions' => $rolePermissionsCount,
                'direct_permissions' => $user->direct_permissions_count,

                // 数据范围统计
                'total_data_scopes' => $roleDataScopesCount + $user->direct_data_scopes_count,
                'role_data_scopes' => $roleDataScopesCount,
                'direct_data_scopes' => $user->direct_data_scopes_count,
            ],

            // 标识
            'has_direct_permissions' => $user->direct_permissions_count > 0,
            'has_direct_data_scopes' => $user->direct_data_scopes_count > 0,
            'has_disabled_roles' => $user->roles->where('enabled', false)->isNotEmpty(),
        ];
    }
}

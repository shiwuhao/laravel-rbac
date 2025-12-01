<?php

namespace Rbac\Actions\User;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取指定用户的权限信息
 *
 * 返回结构化的用户权限详细数据，包括：
 * - roles: 角色列表，每个角色包含其权限和数据范围
 * - directPermissions: 用户直接分配的权限
 * - directDataScopes: 用户直接分配的数据范围
 * - merged: 合并后的所有权限（去重），包含来源标注
 *
 * @example
 * GetUserPermissions::handle([], $userId);
 */
#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:view-permissions', '查看用户权限')]
class GetUserPermissions extends BaseAction
{
    /**
     * 获取指定用户的权限信息
     */
    protected function execute(): array
    {
        $userModel = config('rbac.models.user');

        // 查询用户及其关联数据
        $user = $userModel::query()
            ->with([
                'roles',                // 角色
                'roles.permissions',    // 角色的权限（包括实例权限）
                'roles.dataScopes',     // 角色的数据范围
                'directPermissions',    // 用户的直接权限（包括实例权限）
                'directDataScopes'      // 用户的直接数据范围
            ])
            ->findOrFail($this->context->id());

        return [
            'user' => $user->withoutRelations(),  // 清除所有关联，只返回用户模型的基本属性
            // 角色详细信息
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'enabled' => $role->enabled,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return $this->formatPermission($permission);
                    })->values(),
                    'dataScopes' => $role->dataScopes->map(function ($dataScope) {
                        return $this->formatDataScope($dataScope);
                    })->values(),
                ];
            })->values(),

            // 用户直接权限
            'directPermissions' => $user->directPermissions->map(function ($permission) {
                return $this->formatPermission($permission);
            })->values(),

            // 用户直接数据范围
            'directDataScopes' => $user->directDataScopes->map(function ($dataScope) {
                return $this->formatDataScope($dataScope);
            })->values(),

            // 合并后的所有权限（包含来源标注）
            'merged' => $this->getMergedPermissions($user),
        ];
    }

    /**
     * 获取合并后的权限信息
     */
    private function getMergedPermissions($user): array
    {
        // 收集所有权限和来源
        $permissionSources = [];
        $dataScopeSources = [];

        // 从角色收集权限
        foreach ($user->roles as $role) {
            if (!$role->enabled) {
                continue; // 跳过禁用的角色
            }

            foreach ($role->permissions as $permission) {
                $key = $this->getPermissionKey($permission);
                if (!isset($permissionSources[$key])) {
                    $permissionSources[$key] = [
                        'permission' => $permission,
                        'sources' => [],
                    ];
                }
                $permissionSources[$key]['sources'][] = [
                    'type' => 'role',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'role_slug' => $role->slug,
                ];
            }

            foreach ($role->dataScopes as $dataScope) {
                $key = $dataScope->id;
                if (!isset($dataScopeSources[$key])) {
                    $dataScopeSources[$key] = [
                        'dataScope' => $dataScope,
                        'sources' => [],
                    ];
                }
                $dataScopeSources[$key]['sources'][] = [
                    'type' => 'role',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'role_slug' => $role->slug,
                ];
            }
        }

        // 从直接权限收集
        foreach ($user->directPermissions as $permission) {
            $key = $this->getPermissionKey($permission);
            if (!isset($permissionSources[$key])) {
                $permissionSources[$key] = [
                    'permission' => $permission,
                    'sources' => [],
                ];
            }
            $permissionSources[$key]['sources'][] = [
                'type' => 'direct',
            ];
        }

        // 从直接数据范围收集
        foreach ($user->directDataScopes as $dataScope) {
            $key = $dataScope->id;
            if (!isset($dataScopeSources[$key])) {
                $dataScopeSources[$key] = [
                    'dataScope' => $dataScope,
                    'sources' => [],
                ];
            }
            $dataScopeSources[$key]['sources'][] = [
                'type' => 'direct',
            ];
        }

        // 格式化权限列表
        $allPermissions = [];
        $instancePermissions = [];

        foreach ($permissionSources as $item) {
            $permission = $item['permission'];
            $formatted = $this->formatPermission($permission);
            $formatted['sources'] = $item['sources'];
            $formatted['has_conflict'] = count($item['sources']) > 1;

            // 区分实例权限
            if ($permission->resource_type && $permission->resource_id) {
                $resourceType = $permission->resource_type;
                if (!isset($instancePermissions[$resourceType])) {
                    $instancePermissions[$resourceType] = [];
                }
                $instancePermissions[$resourceType][] = $formatted;
            } else {
                $allPermissions[] = $formatted;
            }
        }

        // 格式化数据范围列表
        $allDataScopes = [];
        foreach ($dataScopeSources as $item) {
            $formatted = $this->formatDataScope($item['dataScope']);
            $formatted['sources'] = $item['sources'];
            $formatted['has_conflict'] = count($item['sources']) > 1;
            $allDataScopes[] = $formatted;
        }

        return [
            'all_permissions' => array_values($allPermissions),
            'instance_permissions' => $instancePermissions,
            'all_data_scopes' => array_values($allDataScopes),
            'stats' => [
                'total_permissions' => count($permissionSources),
                'base_permissions' => count($allPermissions),
                'instance_permissions' => count($permissionSources) - count($allPermissions),
                'total_data_scopes' => count($dataScopeSources),
                'from_roles' => $this->countSourceType($permissionSources, 'role') + $this->countSourceType($dataScopeSources, 'role'),
                'from_direct' => $this->countSourceType($permissionSources, 'direct') + $this->countSourceType($dataScopeSources, 'direct'),
                'conflicts' => $this->countConflicts($permissionSources) + $this->countConflicts($dataScopeSources),
            ],
        ];
    }

    /**
     * 获取权限的唯一标识
     */
    private function getPermissionKey($permission): string
    {
        if ($permission->resource_type && $permission->resource_id) {
            return "{$permission->slug}:{$permission->resource_type}:{$permission->resource_id}";
        }
        return $permission->slug;
    }

    /**
     * 统计指定来源类型的数量
     */
    private function countSourceType(array $sources, string $type): int
    {
        $count = 0;
        foreach ($sources as $item) {
            foreach ($item['sources'] as $source) {
                if ($source['type'] === $type) {
                    $count++;
                    break;
                }
            }
        }
        return $count;
    }

    /**
     * 统计冲突数量
     */
    private function countConflicts(array $sources): int
    {
        $count = 0;
        foreach ($sources as $item) {
            if (count($item['sources']) > 1) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 格式化权限数据
     */
    private function formatPermission($permission): array
    {
        $data = [
            'id' => $permission->id,
            'name' => $permission->name,
            'slug' => $permission->slug,
            'description' => $permission->description,
            'guard_name' => $permission->guard_name,
        ];

        // 如果是实例权限，添加资源信息
        if ($permission->resource_type && $permission->resource_id) {
            $data['resource_type'] = $permission->resource_type;
            $data['resource_id'] = $permission->resource_id;
            $data['is_instance_permission'] = true;
        } else {
            $data['is_instance_permission'] = false;
        }

        return $data;
    }

    /**
     * 格式化数据范围
     */
    private function formatDataScope($dataScope): array
    {
        $data = [
            'id' => $dataScope->id,
            'name' => $dataScope->name,
            'slug' => $dataScope->slug,
            'type' => $dataScope->type,
            'description' => $dataScope->description,
        ];

        // 如果中间表有 constraint 字段
        if (isset($dataScope->pivot->constraint)) {
            $data['constraint'] = $dataScope->pivot->constraint;
        }

        return $data;
    }
}

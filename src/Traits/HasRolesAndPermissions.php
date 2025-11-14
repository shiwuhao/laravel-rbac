<?php

namespace Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Rbac\Contracts\RoleContract;
use Rbac\Contracts\PermissionContract;
use Rbac\Contracts\DataScopeContract;

/**
 * 用户角色和权限特性
 *
 * 为用户模型提供角色和权限的查询、判断功能
 * 
 * 注意：所有写操作（分配、撤销、同步）请使用对应的 Action 类：
 * - AssignRolesToUser: 分配角色
 * - RevokeRoleFromUser: 撤销角色
 * - SyncRolesToUser: 同步角色
 * - AssignPermissionsToUser: 分配权限（需实现）
 * - RevokePermissionFromUser: 撤销权限（需实现）
 * - SyncPermissionsToUser: 同步权限（需实现）
 */
trait HasRolesAndPermissions
{
    /**
     * 用户角色关联
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.role'),
            config('rbac.tables.user_role'),
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * 用户直接权限关联
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.permission'),
            config('rbac.tables.user_permission'),
            'user_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * 用户数据范围关联
     */
    public function dataScopes(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.data_scope'),
            config('rbac.tables.user_data_scope'),
            'user_id',
            'data_scope_id'
        )->withPivot('constraint')->withTimestamps();
    }



    /**
     * 检查用户是否具有指定角色
     */
    public function hasRole(string|RoleContract $role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }

        return $this->roles->contains('id', $role->id);
    }

    /**
     * 检查用户是否具有任一角色
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查用户是否具有所有角色
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查用户是否具有指定权限
     * 
     * @param string|PermissionContract $permission 权限标识或权限实例
     * @param string|null $resourceType 资源类型（可选，用于实例权限）
     * @param int|null $resourceId 资源ID（可选，用于实例权限）
     * @return bool
     */
    public function hasPermission(
        string|PermissionContract $permission,
        ?string $resourceType = null,
        ?int $resourceId = null
    ): bool {
        $permissionSlug = is_string($permission) ? $permission : $permission->slug;

        // 实例权限检查
        if ($resourceType && $resourceId) {
            return $this->hasInstancePermission($permissionSlug, $resourceType, $resourceId);
        }

        // 通用权限检查
        return $this->getAllPermissions()->contains('slug', $permissionSlug);
    }

    /**
     * 检查用户是否具有实例级权限
     * 
     * @param string $permissionSlug 权限标识（如 report:view）
     * @param string $resourceType 资源类型（如 App\Models\Report）
     * @param int $resourceId 资源ID
     * @return bool
     */
    public function hasInstancePermission(string $permissionSlug, string $resourceType, int $resourceId): bool
    {
        $allPermissions = $this->getAllPermissions();

        // 检查是否有具体实例的权限
        $hasInstancePermission = $allPermissions->contains(function ($permission) use ($permissionSlug, $resourceType, $resourceId) {
            return $permission->slug === $permissionSlug
                && $permission->resource_type === $resourceType
                && $permission->resource_id === $resourceId;
        });

        if ($hasInstancePermission) {
            return true;
        }

        // 回退检查：是否有该资源类型的通用权限
        return $allPermissions->contains(function ($permission) use ($permissionSlug) {
            return $permission->slug === $permissionSlug
                && empty($permission->resource_type)
                && empty($permission->resource_id);
        });
    }

    /**
     * 检查用户是否具有任一权限
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPermissions = $this->getAllPermissions()->pluck('slug')->toArray();
        $permissionSlugs = collect($permissions)->map(function ($permission) {
            return is_string($permission) ? $permission : $permission->slug;
        })->toArray();

        return !empty(array_intersect($permissionSlugs, $userPermissions));
    }

    /**
     * 检查用户是否具有所有权限
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->getAllPermissions()->pluck('slug')->toArray();
        $permissionSlugs = collect($permissions)->map(function ($permission) {
            return is_string($permission) ? $permission : $permission->slug;
        })->toArray();

        return empty(array_diff($permissionSlugs, $userPermissions));
    }

    /**
     * 获取用户的所有权限（角色权限 + 直接权限）
     */
    public function getAllPermissions(): Collection
    {
        $cacheKey = config('rbac.cache.key') . '.user_permissions.' . $this->id;
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis' || $cacheDriver === 'memcached') {
            return Cache::remember($cacheKey, config('rbac.cache.expiration_time'), function () {
                // 预加载关联关系
                $this->loadMissing(['roles.permissions', 'directPermissions']);

                // 获取角色权限
                $rolePermissions = $this->roles->flatMap->permissions;

                // 合并直接权限并去重
                return $rolePermissions
                    ->merge($this->directPermissions)
                    ->unique('id');
            });
        }

        return Cache::tags(['rbac', 'user_permissions'])
            ->remember($cacheKey, config('rbac.cache.expiration_time'), function () {
                // 预加载关联关系
                $this->loadMissing(['roles.permissions', 'directPermissions']);

                // 获取角色权限
                $rolePermissions = $this->roles->flatMap->permissions;

                // 合并直接权限并去重
                return $rolePermissions
                    ->merge($this->directPermissions)
                    ->unique('id');
            });
    }

    /**
     * 获取用户在指定权限下的数据范围
     */
    public function getDataScopesForPermission(string|PermissionContract $permission): Collection
    {
        $permissionSlug = is_string($permission) ? $permission : $permission->slug;

        $userPermission = $this->getAllPermissions()
            ->where('slug', $permissionSlug)
            ->first();

        if (!$userPermission) {
            return collect();
        }

        // 获取权限关联的数据范围
        $permissionDataScopes = $userPermission->dataScopes;

        // 获取用户直接关联的数据范围
        $userDataScopes = $this->dataScopes;

        // 合并并去重
        return $permissionDataScopes->merge($userDataScopes)->unique('id');
    }



    /**
     * 检查用户是否具有数据范围
     */
    public function hasDataScope(string|DataScopeContract $dataScope): bool
    {
        if (is_string($dataScope)) {
            return $this->dataScopes->contains('name', $dataScope);
        }

        return $this->dataScopes->contains('id', $dataScope->id);
    }

    /**
     * 检查用户是否为超级管理员
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('rbac.super_admin_role', 'super-admin'));
    }

    /**
     * 清除权限缓存
     */
    public function forgetCachedPermissions(): void
    {
        $cacheKey = config('rbac.cache.key') . '.user_permissions.' . $this->id;
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis' || $cacheDriver === 'memcached') {
            Cache::forget($cacheKey);
        } else {
            Cache::tags(['rbac', 'user_permissions'])->flush();
        }
    }



    /**
     * 模型启动时注册事件
     */
    protected static function bootHasRolesAndPermissions(): void
    {
        static::deleting(function ($model) {
            $model->roles()->detach();
            $model->directPermissions()->detach();
            $model->dataScopes()->detach();
            $model->forgetCachedPermissions();
        });
    }
}
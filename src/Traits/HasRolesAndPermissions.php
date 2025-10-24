<?php

namespace Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;

/**
 * 用户角色和权限特性
 * 
 * 为用户模型提供角色和权限管理功能
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
     * 分配角色给用户
     */
    public function assignRole(string|array|Role $roles): self
    {
        $roles = collect(\Arr::wrap($roles))
            ->map(function ($role) {
                if (is_string($role)) {
                    return Role::where('slug', $role)->first();
                }
                return $role;
            })
            ->filter()
            ->pluck('id');

        $this->roles()->syncWithoutDetaching($roles);
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * 撤销用户角色
     */
    public function removeRole(string|array|Role $roles): self
    {
        $roles = collect(\Arr::wrap($roles))
            ->map(function ($role) {
                if (is_string($role)) {
                    return Role::where('slug', $role)->first();
                }
                return $role;
            })
            ->filter()
            ->pluck('id');

        $this->roles()->detach($roles);
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * 同步用户角色
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = collect($roles)
            ->map(function ($role) {
                if (is_string($role)) {
                    return Role::where('slug', $role)->first()?->id;
                }
                return $role instanceof Role ? $role->id : $role;
            })
            ->filter();

        $this->roles()->sync($roleIds);
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * 直接分配权限给用户
     */
    public function givePermission(string|array|Permission $permissions): self
    {
        $permissions = collect(\Arr::wrap($permissions))
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return Permission::where('slug', $permission)->first();
                }
                return $permission;
            })
            ->filter()
            ->pluck('id');

        $this->directPermissions()->syncWithoutDetaching($permissions);
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * 撤销用户直接权限
     */
    public function revokePermission(string|array|Permission $permissions): self
    {
        $permissions = collect(\Arr::wrap($permissions))
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return Permission::where('slug', $permission)->first();
                }
                return $permission;
            })
            ->filter()
            ->pluck('id');

        $this->directPermissions()->detach($permissions);
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * 同步用户直接权限
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return Permission::where('slug', $permission)->first()?->id;
                }
                return $permission instanceof Permission ? $permission->id : $permission;
            })
            ->filter();

        $this->directPermissions()->sync($permissionIds);
        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * 检查用户是否具有指定角色
     */
    public function hasRole(string|Role $role): bool
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
     */
    public function hasPermission(string|Permission $permission): bool
    {
        $permissionSlug = is_string($permission) ? $permission : $permission->slug;
        
        return $this->getAllPermissions()->contains('slug', $permissionSlug);
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
    public function getDataScopesForPermission(string|Permission $permission): Collection
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
     * 为用户分配数据范围
     */
    public function assignDataScope(string|array|DataScope $dataScopes, ?string $constraint = null): self
    {
        $dataScopes = collect(\Arr::wrap($dataScopes))
            ->map(function ($dataScope) {
                if (is_string($dataScope)) {
                    return DataScope::where('name', $dataScope)->first();
                }
                return $dataScope;
            })
            ->filter()
            ->mapWithKeys(function ($dataScope) use ($constraint) {
                return [$dataScope->id => ['constraint' => $constraint]];
            });

        $this->dataScopes()->syncWithoutDetaching($dataScopes);

        return $this;
    }

    /**
     * 移除用户数据范围
     */
    public function removeDataScope(string|array|DataScope $dataScopes): self
    {
        $dataScopes = collect(\Arr::wrap($dataScopes))
            ->map(function ($dataScope) {
                if (is_string($dataScope)) {
                    return DataScope::where('name', $dataScope)->first();
                }
                return $dataScope;
            })
            ->filter()
            ->pluck('id');

        $this->dataScopes()->detach($dataScopes);

        return $this;
    }

    /**
     * 检查用户是否具有数据范围
     */
    public function hasDataScope(string|DataScope $dataScope): bool
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
     * 检查用户是否有特定资源实例的权限
     */
    public function hasInstancePermission(string $resourceType, int $resourceId, string $operation): bool
    {
        $hasDirectInstancePermission = $this->directPermissions()
            ->where('resource', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('action', $operation)
            ->exists();
            
        if ($hasDirectInstancePermission) {
            return true;
        }
        
        $hasRoleInstancePermission = $this->roles()
            ->whereHas('permissions', function ($query) use ($resourceType, $resourceId, $operation) {
                $query->where('resource', $resourceType)
                     ->where('resource_id', $resourceId)
                     ->where('action', $operation);
            })
            ->exists();
            
        if ($hasRoleInstancePermission) {
            return true;
        }
        
        return $this->hasGeneralPermission($resourceType, $operation);
    }
    
    /**
     * 检查用户是否有资源类型的通用权限
     */
    public function hasGeneralPermission(string $resourceType, string $operation): bool
    {
        $hasDirectGeneralPermission = $this->directPermissions()
            ->where('resource', $resourceType)
            ->whereNull('resource_id')
            ->where('action', $operation)
            ->exists();
            
        if ($hasDirectGeneralPermission) {
            return true;
        }
        
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($resourceType, $operation) {
                $query->where('resource', $resourceType)
                     ->whereNull('resource_id')
                     ->where('action', $operation);
            })
            ->exists();
    }
    
    /**
     * 获取用户对特定资源实例的所有权限
     */
    public function getInstancePermissions(string $resourceType, int $resourceId): array
    {
        $permissions = [];
        
        $directPermissions = $this->directPermissions()
            ->where('resource', $resourceType)
            ->where('resource_id', $resourceId)
            ->pluck('action')
            ->toArray();
            
        $permissions = array_merge($permissions, $directPermissions);
        
        $rolePermissions = $this->roles()
            ->with(['permissions' => function ($query) use ($resourceType, $resourceId) {
                $query->where('resource', $resourceType)
                     ->where('resource_id', $resourceId);
            }])
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('action')
            ->toArray();
            
        $permissions = array_merge($permissions, $rolePermissions);
        
        $generalPermissions = $this->getGeneralPermissions($resourceType);
        $permissions = array_merge($permissions, $generalPermissions);
        
        return array_unique($permissions);
    }
    
    /**
     * 获取用户对资源类型的通用权限
     */
    public function getGeneralPermissions(string $resourceType): array
    {
        $permissions = [];
        
        $directPermissions = $this->directPermissions()
            ->where('resource', $resourceType)
            ->whereNull('resource_id')
            ->pluck('action')
            ->toArray();
            
        $permissions = array_merge($permissions, $directPermissions);
        
        $rolePermissions = $this->roles()
            ->with(['permissions' => function ($query) use ($resourceType) {
                $query->where('resource', $resourceType)
                     ->whereNull('resource_id');
            }])
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('action')
            ->toArray();
            
        $permissions = array_merge($permissions, $rolePermissions);
        
        return array_unique($permissions);
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
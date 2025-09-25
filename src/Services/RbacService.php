<?php

namespace Rbac\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Enums\ActionType;
use Rbac\Enums\DataScopeType;
use Rbac\Enums\GuardType;

/**
 * RBAC 核心服务类
 * 
 * 提供完整的 RBAC 功能管理接口
 */
class RbacService
{
    /**
     * 创建角色
     */
    public function createRole(
        string $name,
        string $slug,
        ?string $description = null,
        string|GuardType $guard = GuardType::WEB
    ): Role {
        $guardValue = $guard instanceof GuardType ? $guard->value : $guard;

        return Role::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'guard_name' => $guardValue,
        ]);
    }

    /**
     * 创建权限
     */
    public function createPermission(
        string $name,
        string $slug,
        string $resource,
        string|ActionType $action,
        ?string $description = null,
        string|GuardType $guard = GuardType::WEB,
        ?array $metadata = null
    ): Permission {
        $actionValue = $action instanceof ActionType ? $action->value : $action;
        $guardValue = $guard instanceof GuardType ? $guard->value : $guard;

        return Permission::create([
            'name' => $name,
            'slug' => $slug,
            'resource' => $resource,
            'action' => $actionValue,
            'description' => $description,
            'guard_name' => $guardValue,
            'metadata' => $metadata,
        ]);
    }

    /**
     * 创建数据范围
     */
    public function createDataScope(
        string $name,
        string|DataScopeType $type,
        ?array $config = null,
        ?string $description = null
    ): DataScope {
        $typeValue = $type instanceof DataScopeType ? $type->value : $type;

        return DataScope::create([
            'name' => $name,
            'type' => $typeValue,
            'config' => $config,
            'description' => $description,
        ]);
    }

    /**
     * 分配权限给角色
     */
    public function assignPermissionToRole(Role $role, Permission $permission): void
    {
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $this->clearRoleCache($role);
    }

    /**
     * 移除角色权限
     */
    public function removePermissionFromRole(Role $role, Permission $permission): void
    {
        $role->permissions()->detach($permission->id);
        $this->clearRoleCache($role);
    }

    /**
     * 分配角色给用户
     */
    public function assignRoleToUser($user, Role $role): void
    {
        $user->roles()->syncWithoutDetaching([$role->id]);
        $this->clearUserCache($user);
    }

    /**
     * 移除用户角色
     */
    public function removeRoleFromUser($user, Role $role): void
    {
        $user->roles()->detach($role->id);
        $this->clearUserCache($user);
    }

    /**
     * 直接分配权限给用户
     */
    public function assignPermissionToUser($user, Permission $permission): void
    {
        $user->directPermissions()->syncWithoutDetaching([$permission->id]);
        $this->clearUserCache($user);
    }

    /**
     * 移除用户直接权限
     */
    public function removePermissionFromUser($user, Permission $permission): void
    {
        $user->directPermissions()->detach($permission->id);
        $this->clearUserCache($user);
    }

    /**
     * 分配数据范围给权限
     */
    public function assignDataScopeToPermission(
        Permission $permission,
        DataScope $dataScope,
        ?string $constraint = null
    ): void {
        $permission->dataScopes()->syncWithoutDetaching([
            $dataScope->id => ['constraint' => $constraint]
        ]);
    }

    /**
     * 分配数据范围给用户
     */
    public function assignDataScopeToUser(
        $user,
        DataScope $dataScope,
        ?string $constraint = null
    ): void {
        $user->dataScopes()->syncWithoutDetaching([
            $dataScope->id => ['constraint' => $constraint]
        ]);
        $this->clearUserCache($user);
    }

    /**
     * 检查用户权限
     */
    public function checkUserPermission($user, string $permissionSlug): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasPermission($permissionSlug);
    }

    /**
     * 获取用户所有权限
     */
    public function getUserPermissions($user): Collection
    {
        if (!$user) {
            return collect();
        }

        return $user->getAllPermissions();
    }

    /**
     * 获取角色所有权限
     */
    public function getRolePermissions(Role $role): Collection
    {
        return $role->permissions;
    }

    /**
     * 获取用户数据范围
     */
    public function getUserDataScopes($user): Collection
    {
        if (!$user) {
            return collect();
        }

        return $user->dataScopes;
    }

    /**
     * 批量创建资源权限
     */
    public function createResourcePermissions(
        string $resource,
        ?array $actions = null,
        string|GuardType $guard = GuardType::WEB
    ): Collection {
        $actions = $actions ?: [
            ActionType::VIEW->value,
            ActionType::CREATE->value,
            ActionType::UPDATE->value,
            ActionType::DELETE->value,
        ];

        $permissions = collect();

        foreach ($actions as $action) {
            $actionType = ActionType::from($action);
            $slug = Permission::generateSlug($resource, $actionType);
            $name = Permission::generateName($resource, $actionType);

            $permission = $this->createPermission(
                $name,
                $slug,
                $resource,
                $actionType,
                null,
                $guard
            );

            $permissions->push($permission);
        }

        return $permissions;
    }

    /**
     * 同步角色权限
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
        $this->clearRoleCache($role);
    }

    /**
     * 同步用户角色
     */
    public function syncUserRoles($user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
        $this->clearUserCache($user);
    }

    /**
     * 同步用户权限
     */
    public function syncUserPermissions($user, array $permissionIds): void
    {
        $user->directPermissions()->sync($permissionIds);
        $this->clearUserCache($user);
    }

    /**
     * 清除用户缓存
     */
    public function clearUserCache($user): void
    {
        if (method_exists($user, 'forgetCachedPermissions')) {
            $user->forgetCachedPermissions();
        }
    }

    /**
     * 清除角色缓存
     */
    public function clearRoleCache(Role $role): void
    {
        // 清除所有关联用户的缓存
        $role->users->each(function ($user) {
            $this->clearUserCache($user);
        });
    }

    /**
     * 获取权限统计信息
     */
    public function getPermissionStats(): array
    {
        return [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'total_data_scopes' => DataScope::count(),
            'permissions_by_resource' => Permission::select('resource')
                ->selectRaw('count(*) as count')
                ->groupBy('resource')
                ->pluck('count', 'resource')
                ->toArray(),
            'permissions_by_action' => Permission::select('action')
                ->selectRaw('count(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }

    /**
     * 查找或创建权限
     */
    public function findOrCreatePermission(
        string $slug,
        string $name,
        string $resource,
        string|ActionType $action,
        string|GuardType $guard = GuardType::WEB
    ): Permission {
        $guardValue = $guard instanceof GuardType ? $guard->value : $guard;
        
        return Permission::firstOrCreate(
            [
                'slug' => $slug,
                'guard_name' => $guardValue,
            ],
            [
                'name' => $name,
                'resource' => $resource,
                'action' => $action instanceof ActionType ? $action->value : $action,
            ]
        );
    }

    /**
     * 清除所有权限缓存
     */
    public function clearAllCache(): void
    {
        $cacheKey = config('rbac.cache.key');
        Cache::flush(); // 简单的全部清除，可以根据需要优化
    }
}
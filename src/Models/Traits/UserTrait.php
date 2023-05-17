<?php

namespace Shiwuhao\Rbac\Models\Traits;

use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * RBAC UserTrait
 */
trait UserTrait
{
    /**
     * 验证管理员
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->id === 1;
    }

    /**
     * 用户和角色的多对多关系
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.role'),
            config('rbac.table.role_user'),
            config('rbac.foreign_key.user'),
            config('rbac.foreign_key.role')
        );
    }

    /**
     * 缓存用户角色集合
     * @return Collection
     */
    public function cacheRoles(): Collection
    {
        $key = 'rbac_roles_for_user_' . $this->primaryKey;
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(config('rbac.table.roles'))->remember($key, config('rbac.ttl'), function () {
                return $this->roles()->get();
            });
        }
        return $this->roles()->get();
    }

    /**
     * 权限节点集合
     * @return Collection
     */
    public function permissions(): Collection
    {
        return $this->roles()->with('permissions.permissible')->get()->pluck('permissions')->collapse()->unique('id')->values();
    }

    /**
     * 缓存用户权限节点集合
     * @return Collection
     */
    public function cachePermissions(): Collection
    {
        $key = 'rbac_permissions_for_user_' . $this->primaryKey;
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(config('rbac.table.permissions'))->remember($key, config('rbac.ttl'), function () {
                return $this->permissions();
            });
        }
        return $this->permissions();
    }

    /**
     * 通过角色标识校验权限
     * @param string|array $roleNames
     * @param bool $and
     * @return bool
     */
    public function hasRole(string|array $roleNames, bool $and = false): bool
    {
        $roleNames = is_array($roleNames) ? [$roleNames] : $roleNames;
        foreach ($roleNames as $roleName) {
            $check = $this->cacheRoles()->pluck('name')->some($roleName);
            if (!$and && $check) return true;
            if ($and && !$check) return false;
        }
        return $and;
    }

    /**
     * 通过权限节点标识校验权限
     * @param string|array $permissionNames
     * @param string $checkColumn
     * @param bool $and
     * @return bool
     */
    public function hasPermission(string|array $permissionNames, string $checkColumn = 'name', bool $and = false): bool
    {
        if ($this->isAdministrator()) return true;

        $permissionNames = is_array($permissionNames) ? $permissionNames : [$permissionNames];
        foreach ($permissionNames as $permissionName) {
            $check = $this->cachePermissions()->pluck('permissible')->pluck($checkColumn)->some($permissionName);
            if (!$and && $check) return true;
            if ($and && !$check) return false;
        }
        return $and;
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clearPermissionCache(): bool
    {
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags([config('rbac.table.roles'), config('rbac.table.permissions')])->flush();
        }
        return false;
    }
}

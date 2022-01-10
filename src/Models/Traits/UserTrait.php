<?php

namespace Shiwuhao\Rbac\Models\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

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
     * 权限节点集合
     * @return Collection
     */
    public function permissions(): Collection
    {
        return $this->roles()->with('permissions.permissible')->get()->pluck('permissions')->collapse()->unique('id')->values();
    }

    /**
     * 通过角色表示校验权限
     * @param $roleName
     * @return bool
     */
    public function hasRole($roleName): bool
    {
        return $this->roles()->pluck('name')->some($roleName);
    }

    /**
     * 鉴权
     * @param $permissionName
     * @param string $type
     * @return bool
     */
    public function hasPermission($permissionName, string $type = 'name'): bool
    {
        if ($type == 'alias') {
            return $this->hasPermissionAlias($permissionName);
        }

        return $this->hasPermissionName($permissionName);
    }

    /**
     * 通过别名鉴权
     * @param $permissionAlias
     * @return bool
     */
    public function hasPermissionAlias($permissionAlias): bool
    {
        if ($this->isAdministrator()) return true;

        return $this->permissions()->pluck('permissible')->pluck('alias')->some($permissionAlias);
    }

    /**
     * 通过name标识鉴权
     * @param $permissionName
     * @return bool
     */
    public function hasPermissionName($permissionName): bool
    {
        if ($this->isAdministrator()) return true;

        return $this->permissions()->pluck('permissible')->pluck('name')->some($permissionName);
    }
}

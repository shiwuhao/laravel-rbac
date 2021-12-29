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
     * 校验管理员
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return true;
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
     * 用户和角色的多对多关系 with permissions.permissible
     * @return BelongsToMany
     */
    public function roleWithPermissions(): BelongsToMany
    {
        return $this->roles()->with('permissions.permissible');
    }

    /**
     * 权限节点集合
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->roleWithPermissions()->get()->pluck('permissions')->collapse()->unique('id')->values();
    }

    /**
     * 权限节点别名集合
     * 多态模型中应该追加alias访问器
     * @return Collection
     */
    public function getPermissionAlias(): Collection
    {
        return $this->getPermissions()->pluck('permissible')->pluck('alias')->values();
    }

    /**
     * 通过别名校验权限
     * @param $alias
     * @return bool
     */
    public function hasPermission($alias): bool
    {
        if ($this->isAdministrator()) return true;

        return $this->getPermissionAlias()->some($alias);
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
}

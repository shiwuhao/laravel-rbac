<?php

namespace Rbac\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface RoleContract
{
    /**
     * 权限关联
     */
    public function permissions(): BelongsToMany;

    /**
     * 用户关联
     */
    public function users(): BelongsToMany;

    /**
     * 检查角色是否具有指定权限
     */
    public function hasPermission(string|PermissionContract $permission): bool;

    /**
     * 检查角色是否具有任一权限
     */
    public function hasAnyPermission(array $permissions): bool;

    /**
     * 检查角色是否具有所有权限
     */
    public function hasAllPermissions(array $permissions): bool;

    /**
     * 分配权限给角色
     */
    public function givePermission(string|array|PermissionContract $permissions): self;

    /**
     * 撤销角色权限
     */
    public function revokePermission(string|array|PermissionContract $permissions): self;

    /**
     * 同步角色权限
     */
    public function syncPermissions(array $permissions): self;
}
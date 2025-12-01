<?php

namespace Rbac\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface PermissionContract
{
    /**
     * 角色关联
     */
    public function roles(): BelongsToMany;

    /**
     * 用户关联（直接权限）
     */
    public function users(): BelongsToMany;

    /**
     * 检查是否为写操作
     */
    public function isWriteOperation(): bool;

    /**
     * 检查是否为实例权限
     */
    public function isInstancePermission(): bool;

    /**
     * 检查是否为通用权限
     */
    public function isGeneralPermission(): bool;
}
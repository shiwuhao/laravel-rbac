<?php

namespace Rbac\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Rbac\Models\DataScope;

/**
 * 数据权限特性
 * 
 * 为模型提供数据权限过滤功能
 */
trait HasDataPermissions
{
    /**
     * 应用数据权限过滤
     */
    public function scopeWithDataPermission(Builder $query, string $permission, $user = null): Builder
    {
        $user = $user ?: auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0'); // 未登录返回空结果
        }

        // 检查用户是否具有该权限
        if (!$user->hasPermission($permission)) {
            return $query->whereRaw('1 = 0'); // 无权限返回空结果
        }

        // 超级管理员跳过数据权限限制
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // 获取用户在该权限下的数据范围
        $dataScopes = $user->getDataScopesForPermission($permission);

        if ($dataScopes->isEmpty()) {
            return $query->whereRaw('1 = 0'); // 无数据范围返回空结果
        }

        // 应用数据范围过滤
        return $query->where(function ($query) use ($dataScopes, $user) {
            foreach ($dataScopes as $dataScope) {
                $query->orWhere(function ($subQuery) use ($dataScope, $user) {
                    $dataScope->applyScope($subQuery, $user);
                });
            }
        });
    }

    /**
     * 检查用户是否可以访问当前模型
     */
    public function canBeAccessedBy($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // 检查用户是否具有该权限
        if (!$user->hasPermission($permission)) {
            return false;
        }

        // 超级管理员可以访问所有数据
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 获取用户在该权限下的数据范围
        $dataScopes = $user->getDataScopesForPermission($permission);

        if ($dataScopes->isEmpty()) {
            return false;
        }

        // 检查是否有任一数据范围允许访问
        return $dataScopes->some(function ($dataScope) use ($user) {
            return $dataScope->canAccess($this, $user);
        });
    }

    /**
     * 获取用户可访问的模型ID列表
     */
    public static function getAccessibleIds(string $permission, $user = null): Collection
    {
        $user = $user ?: auth()->user();
        
        if (!$user) {
            return collect();
        }

        return static::withDataPermission($permission, $user)->pluck('id');
    }

    /**
     * 检查用户是否可以对模型执行指定操作
     */
    public function authorizeAction(string $action, $user = null): bool
    {
        $user = $user ?: auth()->user();
        $permission = $this->getPermissionForAction($action);
        
        return $this->canBeAccessedBy($user, $permission);
    }

    /**
     * 根据操作获取权限标识符
     */
    protected function getPermissionForAction(string $action): string
    {
        $resourceName = $this->getResourceName();
        return "{$resourceName}.{$action}";
    }

    /**
     * 获取资源名称（可重写）
     */
    protected function getResourceName(): string
    {
        return strtolower(class_basename($this));
    }
}
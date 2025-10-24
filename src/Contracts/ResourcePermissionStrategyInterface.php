<?php

namespace Rbac\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * 资源权限策略接口
 * 
 * 定义不同资源类型的权限验证策略
 */
interface ResourcePermissionStrategyInterface
{
    /**
     * 检查权限
     */
    public function checkPermission(Model $user, string $operation, mixed $resource = null): bool;
    
    /**
     * 获取用户可访问的资源ID列表
     */
    public function getAccessibleResourceIds(Model $user, string $operation): array;
    
    /**
     * 应用权限过滤到查询构建器
     */
    public function applyPermissionFilter($query, Model $user, string $operation);
    
    /**
     * 获取支持的资源类型
     */
    public function getResourceType(): string;
}
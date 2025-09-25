<?php

namespace Rbac\Observers;

use Illuminate\Database\Eloquent\Model;
use Rbac\Enums\ActionType;

/**
 * 路由权限同步观察者
 * 
 * 专门用于根据路由自动生成权限的观察者
 */
class RoutePermissionObserver extends PermissionSyncObserver
{
    /**
     * 获取资源类型
     */
    protected function getResourceType(Model $model): string
    {
        return 'Route';
    }

    /**
     * 获取支持的操作类型
     */
    protected function getOperations(Model $model): array
    {
        // 根据HTTP方法确定操作类型
        $httpMethod = $model->http_method ?? 'GET';
        
        return match(strtoupper($httpMethod)) {
            'GET' => [ActionType::VIEW->value],
            'POST' => [ActionType::CREATE->value],
            'PUT', 'PATCH' => [ActionType::UPDATE->value],
            'DELETE' => [ActionType::DELETE->value],
            default => [ActionType::VIEW->value],
        };
    }

    /**
     * 获取权限标识符
     */
    protected function getPermissionSlug(Model $model, string $operation): string
    {
        $routeName = $model->route_name ?? $model->name;
        return str_replace(['.', '/'], ['.', '.'], $routeName);
    }

    /**
     * 获取权限名称
     */
    protected function getPermissionName(Model $model, string $operation): string
    {
        return $model->title ?? $model->name ?? '未命名路由';
    }

    /**
     * 获取权限描述
     */
    protected function getPermissionDescription(Model $model, string $operation): string
    {
        $routeName = $model->route_name ?? $model->name;
        $path = $model->http_path ?? $model->path ?? '';
        $method = $model->http_method ?? 'GET';
        
        return "访问路由: {$routeName} ({$method} {$path})";
    }

    /**
     * 获取关键字段
     */
    protected function getKeyFields(): array
    {
        return ['name', 'route_name', 'title', 'http_method', 'http_path'];
    }
}
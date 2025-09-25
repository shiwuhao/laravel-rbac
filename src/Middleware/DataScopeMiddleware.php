<?php

namespace Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

/**
 * 数据权限中间件
 * 
 * 在请求中注入数据权限查询约束
 */
class DataScopeMiddleware
{
    /**
     * 处理传入的请求
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $permission 权限标识符
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(Request $request, Closure $next, string $permission): SymfonyResponse
    {
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('用户未登录');
        }

        // 检查用户是否具有该权限
        if (!$user->hasPermission($permission)) {
            throw new AuthorizationException("缺少权限: {$permission}");
        }

        // 超级管理员跳过数据权限限制
        if (!$user->isSuperAdmin()) {
            // 获取用户的数据范围
            $dataScopes = $user->getDataScopesForPermission($permission);
            
            // 将数据范围注入到请求中，供后续查询使用
            $request->merge([
                '_data_scopes' => $dataScopes,
                '_data_permission' => $permission,
                '_data_user' => $user,
            ]);
        }

        return $next($request);
    }
}
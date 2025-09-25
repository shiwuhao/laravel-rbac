<?php

namespace Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

/**
 * 角色中间件
 * 
 * 验证用户是否具有指定角色
 */
class RoleMiddleware
{
    /**
     * 处理传入的请求
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string ...$roles 角色列表，支持 | (OR) 和 & (AND) 逻辑
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(Request $request, Closure $next, string ...$roles): SymfonyResponse
    {
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('用户未登录');
        }

        // 超级管理员跳过角色检查
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$this->checkRoles($user, $roles)) {
            throw new AuthorizationException($this->getAuthorizationMessage($roles));
        }

        return $next($request);
    }

    /**
     * 检查角色
     */
    protected function checkRoles($user, array $roles): bool
    {
        if (empty($roles)) {
            return true;
        }

        foreach ($roles as $role) {
            if ($this->evaluateRoleExpression($user, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 评估角色表达式
     */
    protected function evaluateRoleExpression($user, string $expression): bool
    {
        // 处理 OR 逻辑: role1|role2
        if (str_contains($expression, '|')) {
            $orRoles = explode('|', $expression);
            return $user->hasAnyRole(array_map('trim', $orRoles));
        }

        // 处理 AND 逻辑: role1&role2
        if (str_contains($expression, '&')) {
            $andRoles = explode('&', $expression);
            return $user->hasAllRoles(array_map('trim', $andRoles));
        }

        // 单个角色
        return $user->hasRole(trim($expression));
    }

    /**
     * 获取授权失败消息
     */
    protected function getAuthorizationMessage(array $roles): string
    {
        if (count($roles) === 1) {
            return "需要角色: {$roles[0]}";
        }

        return "需要以下角色之一: " . implode(', ', $roles);
    }
}
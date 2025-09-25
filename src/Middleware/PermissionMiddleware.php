<?php

namespace Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

/**
 * 权限中间件
 * 
 * 支持复杂的权限验证逻辑，包括 OR、AND 组合
 */
class PermissionMiddleware
{
    /**
     * 处理传入的请求
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string ...$permissions 权限列表，支持 | (OR) 和 & (AND) 逻辑
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(Request $request, Closure $next, string ...$permissions): SymfonyResponse
    {
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('用户未登录');
        }

        // 超级管理员跳过权限检查
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$this->checkPermissions($user, $permissions)) {
            throw new AuthorizationException($this->getAuthorizationMessage($permissions));
        }

        return $next($request);
    }

    /**
     * 检查权限
     */
    protected function checkPermissions($user, array $permissions): bool
    {
        if (empty($permissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->evaluatePermissionExpression($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 评估权限表达式
     */
    protected function evaluatePermissionExpression($user, string $expression): bool
    {
        // 处理 OR 逻辑: permission1|permission2
        if (str_contains($expression, '|')) {
            $orPermissions = explode('|', $expression);
            return $user->hasAnyPermission(array_map('trim', $orPermissions));
        }

        // 处理 AND 逻辑: permission1&permission2
        if (str_contains($expression, '&')) {
            $andPermissions = explode('&', $expression);
            return $user->hasAllPermissions(array_map('trim', $andPermissions));
        }

        // 处理括号分组: (permission1|permission2)&permission3
        if (str_contains($expression, '(')) {
            return $this->evaluateComplexExpression($user, $expression);
        }

        // 单个权限
        return $user->hasPermission(trim($expression));
    }

    /**
     * 评估复杂权限表达式
     */
    protected function evaluateComplexExpression($user, string $expression): bool
    {
        // 简化版本的表达式解析
        // 可以根据需要扩展为更复杂的解析器
        
        // 移除空格
        $expression = str_replace(' ', '', $expression);
        
        // 处理括号内的 OR 逻辑
        $expression = preg_replace_callback('/\(([^)]+)\)/', function ($matches) use ($user) {
            $innerExpression = $matches[1];
            if (str_contains($innerExpression, '|')) {
                $permissions = explode('|', $innerExpression);
                return $user->hasAnyPermission($permissions) ? '1' : '0';
            }
            return $user->hasPermission($innerExpression) ? '1' : '0';
        }, $expression);
        
        // 处理剩余的 AND 逻辑
        if (str_contains($expression, '&')) {
            $parts = explode('&', $expression);
            foreach ($parts as $part) {
                if ($part === '0' || (!$user->hasPermission($part) && $part !== '1')) {
                    return false;
                }
            }
            return true;
        }
        
        return $expression === '1';
    }

    /**
     * 获取授权失败消息
     */
    protected function getAuthorizationMessage(array $permissions): string
    {
        if (count($permissions) === 1) {
            return "需要权限: {$permissions[0]}";
        }

        return "需要以下权限之一: " . implode(', ', $permissions);
    }
}
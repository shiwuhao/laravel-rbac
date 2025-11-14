<?php

namespace Rbac\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Rbac\Attributes\Permission;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;

/**
 * 基于注解的权限检查中间件
 * 
 * 自动扫描路由对应的 Action/Controller 类上的 Permission 注解
 * 仅对带有注解的路由执行权限校验，未标注注解的接口不校验
 * 
 * @example Route::post('/users', CreateUser::class)->middleware('permission.check');
 */
class PermissionCheckMiddleware
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('用户未登录');
        }

        // 超级管理员跳过权限检查
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // 获取路由 Action/Controller
        $route = $request->route();
        $action = $route?->getAction();

        if (!$action) {
            return $next($request);
        }

        // 提取权限注解
        $permission = $this->extractPermissionFromAction($action);

        // 如果没有找到注解，则不校验（允许通过）
        if (!$permission) {
            return $next($request);
        }

        // 如果注解明确设置 autoCheck=false，则不校验
        if (!$permission->autoCheck) {
            return $next($request);
        }

        // 执行权限校验
        if (!$this->hasPermission($user, $permission->slug)) {
            throw new AuthorizationException(
                "无权限访问: {$permission->name} ({$permission->slug})"
            );
        }

        // 写入当前权限标识，供全局数据范围作用域使用
        $request->attributes->set('rbac.current_permission', $permission->slug);
        app()->instance('rbac.current_permission', $permission->slug);

        return $next($request);
    }

    /**
     * 从 Action 中提取权限注解
     *
     * @param array $action 路由 Action 数组
     * @return Permission|null
     */
    protected function extractPermissionFromAction(array $action): ?Permission
    {
        // 处理 Action 模式：['uses' => 'App\Actions\CreateUser']
        if (isset($action['uses']) && is_string($action['uses'])) {
            return $this->getPermissionFromClass($action['uses']);
        }

        // 处理 Controller 模式：['uses' => 'App\Http\Controllers\UserController@store']
        if (isset($action['controller'])) {
            [$controller, $method] = explode('@', $action['controller']);
            return $this->getPermissionFromMethod($controller, $method);
        }

        return null;
    }

    /**
     * 从类中获取权限注解（用于 Action 模式）
     *
     * @param string $className
     * @return Permission|null
     */
    protected function getPermissionFromClass(string $className): ?Permission
    {
        if (!class_exists($className)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($className);
            $attributes = $reflection->getAttributes(Permission::class);

            if (!empty($attributes)) {
                return $attributes[0]->newInstance();
            }
        } catch (ReflectionException $e) {
            // 忽略反射异常
        }

        return null;
    }

    /**
     * 从方法中获取权限注解（用于 Controller 模式）
     *
     * @param string $className
     * @param string $methodName
     * @return Permission|null
     */
    protected function getPermissionFromMethod(string $className, string $methodName): ?Permission
    {
        if (!class_exists($className)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($className);

            // 优先检查方法上的注解
            if ($reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                $attributes = $method->getAttributes(Permission::class);

                if (!empty($attributes)) {
                    return $attributes[0]->newInstance();
                }
            }

            // 回退到类级别的注解
            $classAttributes = $reflection->getAttributes(Permission::class);
            if (!empty($classAttributes)) {
                return $classAttributes[0]->newInstance();
            }
        } catch (ReflectionException $e) {
            // 忽略反射异常
        }

        return null;
    }

    /**
     * 检查用户是否有指定权限
     *
     * @param mixed $user
     * @param string $permission
     * @return bool
     */
    protected function hasPermission($user, string $permission): bool
    {
        if (!method_exists($user, 'hasPermission')) {
            return false;
        }

        return $user->hasPermission($permission);
    }
}

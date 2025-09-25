<?php

namespace Rbac\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Rbac\Models\Permission;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;

/**
 * 路由权限生成服务
 * 
 * 自动根据路由生成权限节点
 */
class RoutePermissionService
{
    protected RbacService $rbacService;

    public function __construct(RbacService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * 生成所有路由权限
     */
    public function generateAllRoutePermissions(bool $cleanOrphaned = false): Collection
    {
        $routes = $this->getFilteredRoutes();
        $permissions = collect();

        foreach ($routes as $route) {
            $permission = $this->generateRoutePermission($route);
            if ($permission) {
                $permissions->push($permission);
            }
        }

        if ($cleanOrphaned) {
            $this->cleanOrphanedRoutePermissions($permissions);
        }

        return $permissions;
    }

    /**
     * 为单个路由生成权限
     */
    public function generateRoutePermission(\Illuminate\Routing\Route $route): ?Permission
    {
        $routeName = $route->getName();
        
        if (!$routeName || $this->shouldSkipRoute($route)) {
            return null;
        }

        $action = $this->getActionFromRoute($route);
        $slug = $this->generateSlugFromRoute($route);
        $name = $this->generateNameFromRoute($route);
        $resource = $this->getResourceFromRoute($route);

        return $this->rbacService->findOrCreatePermission(
            $slug,
            $name,
            $resource,
            $action,
            $this->getGuardFromRoute($route)
        );
    }

    /**
     * 获取过滤后的路由列表
     */
    protected function getFilteredRoutes(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route) {
                return $route->getName() && 
                       !$this->shouldSkipRoute($route);
            });
    }

    /**
     * 判断是否应该跳过路由
     */
    protected function shouldSkipRoute(\Illuminate\Routing\Route $route): bool
    {
        $skipPatterns = config('rbac.route_permission.skip_patterns', [
            'debugbar.*',
            'telescope.*',
            'horizon.*',
            'ignition.*',
            '_ignition.*',
            'livewire.*',
        ]);

        $routeName = $route->getName();

        foreach ($skipPatterns as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 从路由获取操作类型
     */
    protected function getActionFromRoute(\Illuminate\Routing\Route $route): ActionType
    {
        $methods = $route->methods();
        $primaryMethod = is_array($methods) ? $methods[0] : $methods;

        return match(strtoupper($primaryMethod)) {
            'GET' => ActionType::VIEW,
            'POST' => ActionType::CREATE,
            'PUT', 'PATCH' => ActionType::UPDATE,
            'DELETE' => ActionType::DELETE,
            default => ActionType::VIEW,
        };
    }

    /**
     * 从路由生成权限标识符
     */
    protected function generateSlugFromRoute(\Illuminate\Routing\Route $route): string
    {
        $routeName = $route->getName();
        
        // 替换点号为一致的分隔符，保持层级结构
        return str_replace(['.', '/'], ['.', '.'], $routeName);
    }

    /**
     * 从路由生成权限名称
     */
    protected function generateNameFromRoute(\Illuminate\Routing\Route $route): string
    {
        $routeName = $route->getName();
        
        // 尝试生成更友好的名称
        $segments = explode('.', $routeName);
        $resource = $segments[0] ?? '';
        $action = end($segments);

        $actionType = $this->getActionFromRoute($route);
        $resourceName = $this->formatResourceName($resource);

        return $actionType->label() . $resourceName;
    }

    /**
     * 从路由获取资源类型
     */
    protected function getResourceFromRoute(\Illuminate\Routing\Route $route): string
    {
        $routeName = $route->getName();
        $segments = explode('.', $routeName);
        
        return ucfirst($segments[0] ?? 'Route');
    }

    /**
     * 从路由获取守卫类型
     */
    protected function getGuardFromRoute(\Illuminate\Routing\Route $route): GuardType
    {
        $middleware = $route->gatherMiddleware();
        
        if (in_array('auth:api', $middleware) || in_array('api', $middleware)) {
            return GuardType::API;
        }
        
        if (in_array('auth:admin', $middleware)) {
            return GuardType::ADMIN;
        }
        
        return GuardType::WEB;
    }

    /**
     * 格式化资源名称
     */
    protected function formatResourceName(string $resource): string
    {
        // 将下划线和短横线转换为空格，然后首字母大写
        $formatted = str_replace(['-', '_'], ' ', $resource);
        return ucwords($formatted);
    }

    /**
     * 清理孤立的路由权限
     */
    protected function cleanOrphanedRoutePermissions(Collection $validPermissions): void
    {
        $validSlugs = $validPermissions->pluck('slug')->toArray();
        
        // 查找所有路由相关权限
        $routePermissions = Permission::where('resource', 'Route')->get();
        
        foreach ($routePermissions as $permission) {
            if (!in_array($permission->slug, $validSlugs)) {
                $permission->delete();
            }
        }
    }

    /**
     * 获取路由权限统计
     */
    public function getRoutePermissionStats(): array
    {
        $totalRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn($route) => $route->getName())
            ->count();
            
        $routePermissions = Permission::where('resource', 'Route')->count();
        
        return [
            'total_routes' => $totalRoutes,
            'route_permissions' => $routePermissions,
            'coverage_percentage' => $totalRoutes > 0 ? round(($routePermissions / $totalRoutes) * 100, 2) : 0,
        ];
    }

    /**
     * 根据路由名称模式批量生成权限
     */
    public function generatePermissionsByPattern(string $pattern): Collection
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route) use ($pattern) {
                $routeName = $route->getName();
                return $routeName && fnmatch($pattern, $routeName);
            });

        $permissions = collect();
        
        foreach ($routes as $route) {
            $permission = $this->generateRoutePermission($route);
            if ($permission) {
                $permissions->push($permission);
            }
        }

        return $permissions;
    }
}
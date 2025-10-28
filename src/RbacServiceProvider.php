<?php

namespace Rbac;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Rbac\Services\RbacService;
use Rbac\Services\RoutePermissionService;
use Rbac\Commands\CreateRoleCommand;
use Rbac\Commands\CreatePermissionCommand;
use Rbac\Commands\GenerateRoutePermissionsCommand;
use Rbac\Commands\RbacStatusCommand;
use Rbac\Commands\ClearCacheCommand;
use Rbac\Commands\InstallCommand;
use Rbac\Commands\SeedTestDataCommand;
use Rbac\Commands\QuickSeedCommand;
use Rbac\Middleware\PermissionMiddleware;
use Rbac\Middleware\RoleMiddleware;
use Rbac\Middleware\DataScopeMiddleware;

/**
 * Laravel RBAC 服务提供者
 */
class RbacServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 合并配置
        $this->mergeConfigFrom(__DIR__.'/../config/rbac.php', 'rbac');

        // 注册核心服务
        // @deprecated 从 v2.0 开始，推荐使用 Action 模式
        $this->app->singleton(RbacService::class, function ($app) {
            return new RbacService();
        });

        $this->app->singleton(RoutePermissionService::class, function ($app) {
            return new RoutePermissionService($app->make(RbacService::class));
        });

        // 注册门面
        $this->app->singleton('rbac', function ($app) {
            return $app->make(RbacService::class);
        });
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__.'/../config/rbac.php' => config_path('rbac.php'),
        ], 'rbac-config');

        // 发布迁移文件
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'rbac-migrations');

        // 发布数据填充文件
        $this->publishes([
            __DIR__.'/../database/seeders/' => database_path('seeders'),
        ], 'rbac-seeders');

        // 发布API路由文件
        $this->publishes([
            __DIR__.'/../routes/api.php' => base_path('routes/rbac-api.php'),
        ], 'rbac-routes');

        // 发布Actions文件（可自定义业务逻辑）
        $this->publishes([
            __DIR__.'/Actions/' => app_path('Actions/Rbac/'),
        ], 'rbac-actions');

        // 加载迁移文件
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 加载纯API路由文件
        if (config('rbac.api.enabled', true)) {
            $this->loadApiRoutes();
        }

        // 注册命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateRoleCommand::class,
                CreatePermissionCommand::class,
                GenerateRoutePermissionsCommand::class,
                RbacStatusCommand::class,
                ClearCacheCommand::class,
                InstallCommand::class,
                SeedTestDataCommand::class,
                QuickSeedCommand::class,
                \Rbac\Commands\ScanPermissionAnnotationsCommand::class,
                \Rbac\Commands\SyncPermissionsFromRoutesCommand::class,
                \Rbac\Commands\InitPackagePermissionsCommand::class,
                \Rbac\Commands\ListPermissionsCommand::class,
            ]);
        }

        // 注册中间件
        $this->registerMiddleware();

        // 注册权限门
        $this->registerGates();

        // 注册 Blade 指令
        $this->registerBladeDirectives();

        // 注册模型观察者
        $this->registerObservers();

        // 注册路由权限生成
        $this->registerRoutePermissionGeneration();
    }

    /**
     * 加载API路由
     */
    protected function loadApiRoutes(): void
    {
        $config = config('rbac.api', []);
        
        \Illuminate\Support\Facades\Route::middleware($config['middleware'] ?? ['api', 'auth:sanctum'])
            ->prefix($config['prefix'] ?? 'api/rbac')
            ->name($config['name_prefix'] ?? 'rbac.api.')
            ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('data-scope', DataScopeMiddleware::class);
    }

    /**
     * 注册权限门
     */
    protected function registerGates(): void
    {
        Gate::before(function ($user, $ability) {
            // 超级管理员拥有所有权限
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // 检查用户是否具有该权限
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability) ?: null;
            }

            return null;
        });
    }

    /**
     * 注册 Blade 指令
     */
    protected function registerBladeDirectives(): void
    {
        // @permission('permission.slug')
        Blade::if('permission', function ($permission) {
            $user = auth()->user();
            return $user && $user->hasPermission($permission);
        });

        // @role('role.slug')
        Blade::if('role', function ($role) {
            $user = auth()->user();
            return $user && $user->hasRole($role);
        });

        // @anypermission('perm1', 'perm2')
        Blade::if('anypermission', function (...$permissions) {
            $user = auth()->user();
            return $user && $user->hasAnyPermission($permissions);
        });

        // @allpermissions('perm1', 'perm2')
        Blade::if('allpermissions', function (...$permissions) {
            $user = auth()->user();
            return $user && $user->hasAllPermissions($permissions);
        });

        // @anyrole('role1', 'role2')
        Blade::if('anyrole', function (...$roles) {
            $user = auth()->user();
            return $user && $user->hasAnyRole($roles);
        });

        // @allroles('role1', 'role2')
        Blade::if('allroles', function (...$roles) {
            $user = auth()->user();
            return $user && $user->hasAllRoles($roles);
        });
    }

    /**
     * 注册模型观察者
     */
    protected function registerObservers(): void
    {
        // 如果启用了自动权限同步
        if (config('rbac.auto_sync_permissions', false)) {
            // 这里可以注册默认的观察者
            // 具体的观察者需要在应用中手动注册
        }
    }

    /**
     * 注册路由权限生成
     */
    protected function registerRoutePermissionGeneration(): void
    {
        if (config('rbac.route_permission.auto_generate', false)) {
            // 在应用启动后自动生成路由权限
            $this->app->booted(function () {
                if (!$this->app->runningInConsole() || $this->app->runningUnitTests()) {
                    return;
                }

                try {
                    $routePermissionService = $this->app->make(RoutePermissionService::class);
                    $routePermissionService->generateAllRoutePermissions();
                } catch (\Exception $e) {
                    // 静默处理，避免影响应用启动
                    logger()->warning('自动生成路由权限失败: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * 获取提供的服务
     */
    public function provides(): array
    {
        return [
            RbacService::class,
            RoutePermissionService::class,
            'rbac',
        ];
    }
}
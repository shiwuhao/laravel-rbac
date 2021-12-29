<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/15
 * Time: 1:52 PM
 */

namespace Shiwuhao\Rbac;


use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Shiwuhao\Rbac\Commands\CreateRole;
use Shiwuhao\Rbac\Commands\GeneratePermissions;
use Shiwuhao\Rbac\Contracts\PermissionInterface;
use Shiwuhao\Rbac\Contracts\RoleInterface;

/**
 * Class RbacServiceProvider
 * @package Shiwuhao\Rbac
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishConfig();
        $this->publishMigrations();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rbac.php', 'rbac');
        $this->registerRbacService();
        $this->registerAlias();
        $this->registerCommand();
        $this->registerModelBindings();
    }

    /**
     * 注册 Rbac 服务
     */
    protected function registerRbacService()
    {
        $this->app->singleton(Rbac::class, function ($app) {
            return new Rbac($app);
        });
    }

    /**
     * register Rbac Alias
     */
    protected function registerAlias()
    {
        $this->app->alias(Rbac::class, 'rbac');
    }

    /**
     * register command
     */
    protected function registerCommand()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GeneratePermissions::class,
                CreateRole::class,
            ]);
        }
    }

    /**
     * register model bindings
     */
    protected function registerModelBindings()
    {
        $config = config('rbac.model');
        $this->app->bind(RoleInterface::class, $config['role']);
        $this->app->bind(PermissionInterface::class, $config['permission']);
    }

    /**
     * publish config
     */
    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/rbac.php' => config_path('rbac.php'),
        ], 'config');
    }

    /**
     * publish migrations
     */
    protected function publishMigrations()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/15
 * Time: 1:52 PM
 */

namespace Shiwuhao\Rbac;


use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
            __DIR__ . '/../database/migrations/create_rbac_tables.php' => database_path('migrations'),
        ], 'migrations');
    }
}

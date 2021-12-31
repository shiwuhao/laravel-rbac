<?php

namespace Shiwuhao\Rbac\Tests;

use Shiwuhao\Rbac\Rbac;
use Shiwuhao\Rbac\RbacServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RbacServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'rbac' => Rbac::class
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('filesystems.disks.local.root', __DIR__ . '/Data/Disks/Local');
        $app['config']->set('filesystems.disks.test', [
            'driver' => 'local',
            'root'   => __DIR__ . '/Data/Disks/Test',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'mysql',
            'host'     => env('DB_HOST'),
            'port'     => env('DB_PORT'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ]);

        $app['config']->set('view.paths', [
            __DIR__ . '/Data/Stubs/Views',
        ]);
    }
}
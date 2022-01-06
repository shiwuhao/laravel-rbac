<?php

namespace Shiwuhao\Rbac\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Shiwuhao\Rbac\Rbac;

/**
 *
 */
class ServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function register_single_instance()
    {
        $rbac = $this->app->make(Rbac::class);
        $this->assertInstanceOf(Rbac::class, $rbac);

        $this->assertSame($rbac, $this->app->make(Rbac::class));
    }

    /**
     * @test
     */
    public function boot_load_config()
    {
        $this->assertTrue(Config::has('rbac.model'));
        $this->assertTrue(is_array(Config::get('rbac.model')));

        $this->assertTrue(Config::has('rbac.table'));
        $this->assertTrue(is_array(Config::get('rbac.table')));
    }

    /**
     * @test
     */
    public function boot_register_commands()
    {
        $commands = Artisan::all();
        $this->assertTrue(Arr::has($commands, 'rbac:auto-generate-actions'));
    }
}
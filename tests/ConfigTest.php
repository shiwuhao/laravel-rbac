<?php

namespace Shiwuhao\Rbac\Tests;

/**
 *
 */
class ConfigTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    /**
     * @test
     */
    public function it_loads_config_facade()
    {
        $this->assertEquals('testing', \Config::get('database.default'));
    }

    /**
     * @test
     */
    public function it_loads_config_helper()
    {
        $this->assertEquals('testing', config('database.default'));
    }
}
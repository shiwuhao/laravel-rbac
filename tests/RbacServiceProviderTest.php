<?php

namespace Shiwuhao\Rbac\Tests;

use Shiwuhao\Rbac\Rbac;

class RbacServiceProviderTest extends TestCase
{

    /**
     * @test
     */
    public function is_bound()
    {
        $this->assertTrue($this->app->bound('rbac'));
    }

    /**
     * @test
     */
    public function has_aliased()
    {
        dump($this->app->getAlias(Rbac::class),Rbac::class);
        $this->assertTrue($this->app->isAlias(Rbac::class));
        $this->assertEquals('rbac', $this->app->getAlias(Rbac::class));
    }
}
<?php

namespace Shiwuhao\Rbac;


/**
 * Class Rbac
 * @package Shiwuhao\Rbac
 */
class Rbac
{
    /**
     * @var
     */
    protected $app;

    /**
     * Rbac constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->app->auth->user();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->user(), $name], $arguments);
    }
}

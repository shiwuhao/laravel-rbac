<?php

namespace Rbac\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * RBAC基础控制器
 * 
 * 提供统一的基础功能，所有RBAC控制器都继承此类
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
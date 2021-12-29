<?php

namespace Shiwuhao\Rbac\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class PermissionMiddleware
 * @package Shiwuhao\Rbac\Middleware
 */
class PermissionMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param null $name
     * @return mixed
     */
    public function handle($request, Closure $next, $name = null)
    {
        $type = !empty($name) ? 'name' : 'alias';
        $name = !empty($name) ? $name : strtolower($request->method()) . ',' . $request->route()->uri();
        if (Auth::guest() || !$request->user()->hasPermission($name, $type)) {
            abort(403, 'forbidden');
        }

        return $next($request);
    }
}

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
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $alias = strtolower($request->method()) . ',' . $request->route()->uri();
        if (Auth::guest() || !$request->user()->hasPermission($alias)) {
            abort(403, 'forbidden');
        }

        return $next($request);
    }
}

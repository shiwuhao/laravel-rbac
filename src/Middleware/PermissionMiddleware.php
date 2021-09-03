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
     * @param $permissions
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $permission = $request->method() . ',' . $request->route()->uri();
        if (Auth::guest() || !Auth::user()->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}

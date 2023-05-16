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
     * @param null $permissionName
     * @param string $checkColumn
     * @return mixed
     */
    public function handle($request, Closure $next, $permissionName = null, string $checkColumn = 'alias'): mixed
    {
        $permissionName = !empty($permissionName) ? $permissionName : strtolower($request->method()) . ',' . $request->route()->uri();
        if (Auth::guest() || !$request->user()->hasPermission($permissionName, $checkColumn)) {
            abort(403, 'forbidden');
        }

        return $next($request);
    }
}

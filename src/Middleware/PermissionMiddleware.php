<?php

namespace Shiwuhao\Rbac\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        
        $permission = strtolower($request->method()) . ',' . $request->route()->uri();
//        print_r($permission);
//        Log::info('me:',$request->user()->hasPermission($permission) );
        if (Auth::guest() || !$request->user()->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}

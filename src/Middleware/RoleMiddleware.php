<?php

namespace Shiwuhao\Rbac\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class Role
 * @package Zizaco\Entrust\Middleware
 */
class RoleMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        if (Auth::guest() || !Auth::user()->hasRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}

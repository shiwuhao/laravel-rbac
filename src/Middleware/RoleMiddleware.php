<?php

namespace Shiwuhao\Rbac\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * RoleMiddleware
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
        if (Auth::guest() || !$request->user()->hasRole($roles)) {
            abort(403, 'forbidden');
        }

        return $next($request);
    }
}

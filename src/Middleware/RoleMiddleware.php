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
        $delimiter = config('rbac.delimiter', '|');
        $roles = is_array($roles) ? $roles : explode($delimiter, trim($roles, $delimiter));

        if (Auth::guest() || !$request->user()->hasRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}

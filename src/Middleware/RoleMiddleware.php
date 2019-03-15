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
     * 分隔符
     */
    const DELIMITER = '|';

    /**
     * @param $request
     * @param Closure $next
     * @param $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        $roles = is_array($roles) ? $roles : explode(self::DELIMITER, $roles);

        if (Auth::guest() || !$request->user()->hasRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}

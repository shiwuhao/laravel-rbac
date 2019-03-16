<?php

namespace Shiwuhao\Rbac\Middleware;

use Closure;

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
    public function handle($request, Closure $next, $permissions)
    {
        $delimiter = config('rbac.delimiter', '|');
        $permissions = is_array($permissions) ? $permissions : explode($delimiter, trim($permissions, $delimiter));

        if ($this->auth->guest() || !$request->user()->can($permissions)) {
            abort(403);
        }

        return $next($request);
    }
}

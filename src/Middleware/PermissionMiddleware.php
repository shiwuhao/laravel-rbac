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
     * 分隔符
     */
    const DELIMITER = '|';

    /**
     * @param $request
     * @param Closure $next
     * @param $permissions
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions)
    {
        $permissions = is_array($permissions) ? $permissions : explode(self::DELIMITER, $permissions);

        if ($this->auth->guest() || !$request->user()->can($permissions)) {
            abort(403);
        }

        return $next($request);
    }
}

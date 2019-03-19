<?php

namespace Shiwuhao\Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Shiwuhao\Rbac\Exceptions\InvalidArgumentException;

/**
 * Class ModelPermissionMiddleware
 * @package Shiwuhao\Rbac\Middleware
 */
class PermissionModelMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $related
     * @param $id
     * @return mixed
     */
    public function handle($request, Closure $next, $related, $id)
    {
        $id = !empty($id) ? $id : $request->route('id');

        if (Auth::guest() || !Auth::user()->hasPermissionModel($related, $id)) {
            abort(403);
        }

        return $next($request);
    }
}

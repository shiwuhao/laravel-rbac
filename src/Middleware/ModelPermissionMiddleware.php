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
class ModelPermissionMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $action
     * @param null $id
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function handle($request, Closure $next, $action, $id = null)
    {
        $id = !empty($id) ? $id : $request->route('id');
        $action = "has" . ucfirst($action);
        $userModel = config('rbac.model.user');
        if (!method_exists(app($userModel), $action)) {
            throw new InvalidArgumentException("method $action not exists in $userModel");
        }

        if (Auth::guest() || !Auth::user()->$action($id)) {
            abort(403);
        }

        return $next($request);
    }
}

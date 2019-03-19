<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:43 PM
 */

namespace Shiwuhao\Rbac\Traits;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait AuthorizesRequestTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait AuthorizesRequestTrait
{
    /**
     * 检测用户角色
     * @param $role
     */
    public function hasRole($role)
    {
        if (Auth::guest() || !Auth::user()->hasRole($role)) {
            abort(403);
        }
    }

    /**
     * 检测用户权限节点
     * @param $permission
     */
    public function hasPermission($permission)
    {
        if (Auth::guest() || !Auth::user()->hasPermission($permission)) {
            abort(403);
        }
    }

    /**
     * 检测用户模型权限节点
     * @param $id
     * @param Model $model
     */
    public function hasPermissionModel($id, Model $model)
    {
        if (Auth::guest() || !Auth::user()->hasPermissionModel($id, $model)) {
            abort(403);
        }
    }
}

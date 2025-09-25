<?php

namespace Rbac\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Rbac\Actions\UserPermissions\{
    GetUserPermissionsAction,
    AssignRoleAction,
    RevokeRoleAction,
    GrantPermissionAction,
    RevokePermissionAction
};
use Rbac\Http\Controllers\Controller;

/**
 * 用户权限控制器
 * 
 * 负责处理用户权限相关的HTTP请求，所有业务逻辑都在Actions中实现。
 * 控制器只负责调用对应的Action并返回结果。
 */
class UserPermissionController extends Controller
{
    /**
     * 获取用户权限信息
     */
    public function show(Request $request, GetUserPermissionsAction $action, $user): JsonResponse
    {
        return $action->execute($request, $user);
    }

    /**
     * 为用户分配角色
     */
    public function assignRole(Request $request, AssignRoleAction $action, $user): JsonResponse
    {
        return $action->execute($request, $user);
    }

    /**
     * 撤销用户角色
     */
    public function revokeRole(Request $request, RevokeRoleAction $action, $user, $role): JsonResponse
    {
        return $action->execute($request, $user, $role);
    }

    /**
     * 为用户授予权限
     */
    public function grantPermission(Request $request, GrantPermissionAction $action, $user): JsonResponse
    {
        return $action->execute($request, $user);
    }

    /**
     * 撤销用户权限
     */
    public function revokePermission(Request $request, RevokePermissionAction $action, $user, $permission): JsonResponse
    {
        return $action->execute($request, $user, $permission);
    }
}
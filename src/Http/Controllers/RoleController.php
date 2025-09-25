<?php

namespace Rbac\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Rbac\Actions\Roles\{
    GetRolesAction,
    CreateRoleAction,
    ShowRoleAction,
    UpdateRoleAction,
    DeleteRoleAction
};
use Rbac\Http\Controllers\Controller;

/**
 * 角色控制器
 * 
 * 负责处理角色相关的HTTP请求，所有业务逻辑都在Actions中实现。
 * 控制器只负责调用对应的Action并返回结果。
 */
class RoleController extends Controller
{
    /**
     * 获取角色列表
     */
    public function index(Request $request, GetRolesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * 创建新角色
     */
    public function store(Request $request, CreateRoleAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * 显示指定角色
     */
    public function show(Request $request, ShowRoleAction $action, $role): JsonResponse
    {
        return $action->execute($request, $role);
    }

    /**
     * 更新指定角色
     */
    public function update(Request $request, UpdateRoleAction $action, $role): JsonResponse
    {
        return $action->execute($request, $role);
    }

    /**
     * 删除指定角色
     */
    public function destroy(Request $request, DeleteRoleAction $action, $role): JsonResponse
    {
        return $action->execute($request, $role);
    }
}
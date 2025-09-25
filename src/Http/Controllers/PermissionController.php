<?php

namespace Rbac\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Rbac\Actions\Permissions\{
    GetPermissionsAction,
    CreatePermissionAction,
    ShowPermissionAction,
    UpdatePermissionAction,
    DeletePermissionAction
};
use Rbac\Http\Controllers\Controller;

/**
 * 权限控制器
 * 
 * 负责处理权限相关的HTTP请求，所有业务逻辑都在Actions中实现。
 * 控制器只负责调用对应的Action并返回结果。
 */
class PermissionController extends Controller
{
    /**
     * 获取权限列表
     */
    public function index(Request $request, GetPermissionsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * 创建新权限
     */
    public function store(Request $request, CreatePermissionAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * 显示指定权限
     */
    public function show(Request $request, ShowPermissionAction $action, $permission): JsonResponse
    {
        return $action->execute($request, $permission);
    }

    /**
     * 更新指定权限
     */
    public function update(Request $request, UpdatePermissionAction $action, $permission): JsonResponse
    {
        return $action->execute($request, $permission);
    }

    /**
     * 删除指定权限
     */
    public function destroy(Request $request, DeletePermissionAction $action, $permission): JsonResponse
    {
        return $action->execute($request, $permission);
    }
}
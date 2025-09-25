<?php

namespace Rbac\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Rbac\Actions\DataScopes\{
    GetDataScopesAction,
    CreateDataScopeAction,
    ShowDataScopeAction,
    UpdateDataScopeAction,
    DeleteDataScopeAction
};
use Rbac\Http\Controllers\Controller;

/**
 * 数据范围控制器
 * 
 * 负责处理数据范围相关的HTTP请求，所有业务逻辑都在Actions中实现。
 * 控制器只负责调用对应的Action并返回结果。
 */
class DataScopeController extends Controller
{
    /**
     * 获取数据范围列表
     */
    public function index(Request $request, GetDataScopesAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * 创建新数据范围
     */
    public function store(Request $request, CreateDataScopeAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * 显示指定数据范围
     */
    public function show(Request $request, ShowDataScopeAction $action, $dataScope): JsonResponse
    {
        return $action->execute($request, $dataScope);
    }

    /**
     * 更新指定数据范围
     */
    public function update(Request $request, UpdateDataScopeAction $action, $dataScope): JsonResponse
    {
        return $action->execute($request, $dataScope);
    }

    /**
     * 删除指定数据范围
     */
    public function destroy(Request $request, DeleteDataScopeAction $action, $dataScope): JsonResponse
    {
        return $action->execute($request, $dataScope);
    }
}
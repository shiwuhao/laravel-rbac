<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 获取权限列表 Action
 */
class GetPermissionsAction extends BaseAction
{
    /**
     * 执行获取权限列表操作
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            $query = Permission::query()->with(['parent', 'children']);

            // 搜索和筛选逻辑
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('resource', 'like', "%{$search}%");
                });
            }

            if ($request->filled('resource')) {
                $query->where('resource', $request->input('resource'));
            }

            if ($request->filled('guard_name')) {
                $query->where('guard_name', $request->input('guard_name'));
            }

            // 排序和分页
            $sortBy = $request->input('sort_by', 'sort_order');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = min($request->input('per_page', 15), 100);
            $permissions = $query->paginate($perPage);

            return $this->paginated($permissions, '权限列表获取成功');

        } catch (\Throwable $e) {
            return $this->handleException($e, 'GetPermissions');
        }
    }
}
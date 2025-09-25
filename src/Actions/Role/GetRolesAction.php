<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 获取角色列表 Action
 */
class GetRolesAction extends BaseAction
{
    /**
     * 执行获取角色列表操作
     * 
     * @param Request $request 请求对象
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            $query = Role::query()->with(['permissions:id,name,slug']);

            // 搜索过滤
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 守护名称过滤
            if ($request->filled('guard_name')) {
                $query->where('guard_name', $request->input('guard_name'));
            }

            // 状态过滤
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // 数据范围类型过滤
            if ($request->filled('data_scope_type')) {
                $query->where('data_scope_type', $request->input('data_scope_type'));
            }

            // 排序
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // 分页
            $perPage = min($request->input('per_page', 15), 100);
            $roles = $query->paginate($perPage);

            $this->log('get_roles', ['count' => $roles->count()]);

            return $this->paginated($roles, '角色列表获取成功');

        } catch (\Throwable $e) {
            return $this->handleException($e, 'GetRoles');
        }
    }
}
<?php

namespace Rbac\Actions\UserPermission;

use Rbac\Actions\BaseAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\User;

/**
 * 获取用户权限列表 Action
 */
class GetUserPermissionsAction extends BaseAction
{
    /**
     * 执行获取用户权限列表操作
     * 
     * @param Request $request 请求对象
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            $query = User::query()->with(['roles.permissions', 'permissions']);

            // 搜索过滤
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // 角色过滤
            if ($request->filled('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('slug', $request->input('role'));
                });
            }

            // 排序
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // 分页
            $perPage = min($request->input('per_page', 15), 100);
            $users = $query->paginate($perPage);

            $this->log('get_user_permissions', ['count' => $users->count()]);

            return $this->paginated($users, '用户权限列表获取成功');

        } catch (\Throwable $e) {
            return $this->handleException($e, 'GetUserPermissions');
        }
    }
}
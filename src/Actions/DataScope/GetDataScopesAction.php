<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Models\DataScope;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 获取数据范围列表 Action
 */
class GetDataScopesAction extends BaseAction
{
    /**
     * 执行获取数据范围列表操作
     * 
     * @param Request $request 请求对象
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            $query = DataScope::query()->with(['rules']);

            // 搜索过滤
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 类型过滤
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            // 状态过滤
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // 排序
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // 分页
            $perPage = min($request->input('per_page', 15), 100);
            $dataScopes = $query->paginate($perPage);

            $this->log('get_data_scopes', ['count' => $dataScopes->count()]);

            return $this->paginated($dataScopes, '数据范围列表获取成功');

        } catch (\Throwable $e) {
            return $this->handleException($e, 'GetDataScopes');
        }
    }
}
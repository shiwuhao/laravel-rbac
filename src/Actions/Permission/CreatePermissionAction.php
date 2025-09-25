<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * 创建权限 Action
 */
class CreatePermissionAction extends BaseAction
{
    /**
     * 执行创建权限操作
     * 
     * @param Request $request 请求对象
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            // 验证请求数据
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:rbac_permissions,slug',
                'description' => 'nullable|string|max:500',
                'guard_name' => 'required|string|in:web,api',
                'resource' => 'nullable|string|max:100',
                'action' => 'nullable|string|max:50',
                'parent_id' => 'nullable|exists:rbac_permissions,id',
                'sort_order' => 'integer|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // 创建权限
            $permission = Permission::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'guard_name' => $validated['guard_name'],
                'resource' => $validated['resource'] ?? null,
                'action' => $validated['action'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->log('create_permission', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            return $this->success($permission, '权限创建成功', 201);

        } catch (\Throwable $e) {
            return $this->handleException($e, 'CreatePermission');
        }
    }
}
<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 创建角色 Action
 */
class CreateRoleAction extends BaseAction
{
    /**
     * 执行创建角色操作
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
                'slug' => 'required|string|max:255|unique:rbac_roles,slug',
                'description' => 'nullable|string|max:500',
                'guard_name' => 'required|string|in:web,api',
                'data_scope_type' => 'nullable|string|in:all,custom,department,department_and_sub,only_self',
                'data_scope_rules' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // 创建角色
            $role = Role::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'guard_name' => $validated['guard_name'],
                'data_scope_type' => $validated['data_scope_type'] ?? 'all',
                'data_scope_rules' => $validated['data_scope_rules'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->log('create_role', ['role_id' => $role->id, 'role_name' => $role->name]);

            return $this->success($role, '角色创建成功', 201);

        } catch (\Throwable $e) {
            return $this->handleException($e, 'CreateRole');
        }
    }
}
<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * 更新角色 Action
 */
class UpdateRoleAction extends BaseAction
{
    /**
     * 执行更新角色操作
     * 
     * @param Request $request 请求对象
     * @param Role $role 角色实例
     * @return JsonResponse
     */
    public function execute(Request $request, Role $role): JsonResponse
    {
        try {
            // 验证请求数据
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    "unique:rbac_roles,slug,{$role->id}"
                ],
                'description' => 'nullable|string|max:500',
                'guard_name' => 'sometimes|required|string|in:web,api',
                'data_scope_type' => 'nullable|string|in:all,custom,department,department_and_sub,only_self',
                'data_scope_rules' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // 更新角色
            $role->update($validated);

            // 重新加载关联数据
            $role->load(['permissions:id,name,slug']);

            $this->log('update_role', ['role_id' => $role->id, 'role_name' => $role->name]);

            return $this->success($role, '角色更新成功');

        } catch (\Throwable $e) {
            return $this->handleException($e, 'UpdateRole');
        }
    }
}
<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * 分配角色权限 Action
 */
class AssignRolePermissionsAction extends BaseAction
{
    /**
     * 执行分配角色权限操作
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
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'exists:rbac_permissions,id',
                'replace' => 'boolean', // 是否替换现有权限
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();
            $permissionIds = $validated['permission_ids'];
            $replace = $validated['replace'] ?? false;

            // 验证权限是否存在且守护名称匹配
            $permissions = Permission::whereIn('id', $permissionIds)
                ->where('guard_name', $role->guard_name)
                ->get();

            if ($permissions->count() !== count($permissionIds)) {
                return $this->error('部分权限不存在或守护名称不匹配', 422);
            }

            if ($replace) {
                // 替换现有权限
                $role->permissions()->sync($permissionIds);
                $message = '角色权限替换成功';
            } else {
                // 添加权限（去重）
                $existingIds = $role->permissions()->pluck('id')->toArray();
                $newIds = array_diff($permissionIds, $existingIds);
                
                if (empty($newIds)) {
                    return $this->error('所有权限已存在', 422);
                }
                
                $role->permissions()->attach($newIds);
                $message = '角色权限分配成功';
            }

            // 重新加载权限数据
            $role->load(['permissions:id,name,slug']);

            $this->log('assign_role_permissions', [
                'role_id' => $role->id,
                'permission_count' => count($permissionIds),
                'replace' => $replace
            ]);

            return $this->success($role, $message);

        } catch (\Throwable $e) {
            return $this->handleException($e, 'AssignRolePermissions');
        }
    }
}
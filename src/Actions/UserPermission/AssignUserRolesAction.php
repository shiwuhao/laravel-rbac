<?php

namespace Rbac\Actions\UserPermission;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\User;

/**
 * 分配用户角色 Action
 */
class AssignUserRolesAction extends BaseAction
{
    /**
     * 执行分配用户角色操作
     * 
     * @param Request $request 请求对象
     * @param User $user 用户实例
     * @return JsonResponse
     */
    public function execute(Request $request, User $user): JsonResponse
    {
        try {
            // 验证请求数据
            $validator = Validator::make($request->all(), [
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:rbac_roles,id',
                'replace' => 'boolean', // 是否替换现有角色
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();
            $roleIds = $validated['role_ids'];
            $replace = $validated['replace'] ?? false;

            // 验证角色是否存在
            $roles = Role::whereIn('id', $roleIds)->get();

            if ($roles->count() !== count($roleIds)) {
                return $this->error('部分角色不存在', 422);
            }

            if ($replace) {
                // 替换现有角色
                $user->roles()->sync($roleIds);
                $message = '用户角色替换成功';
            } else {
                // 添加角色（去重）
                $existingIds = $user->roles()->pluck('id')->toArray();
                $newIds = array_diff($roleIds, $existingIds);
                
                if (empty($newIds)) {
                    return $this->error('所有角色已存在', 422);
                }
                
                $user->roles()->attach($newIds);
                $message = '用户角色分配成功';
            }

            // 重新加载角色数据
            $user->load(['roles:id,name,slug', 'permissions:id,name,slug']);

            $this->log('assign_user_roles', [
                'user_id' => $user->id,
                'role_count' => count($roleIds),
                'replace' => $replace
            ]);

            return $this->success($user, $message);

        } catch (\Throwable $e) {
            return $this->handleException($e, 'AssignUserRoles');
        }
    }
}
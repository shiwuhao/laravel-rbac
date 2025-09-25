<?php

namespace Rbac\Actions\Role;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;
use Illuminate\Http\JsonResponse;

/**
 * 删除角色 Action
 */
class DeleteRoleAction extends BaseAction
{
    /**
     * 执行删除角色操作
     * 
     * @param Role $role 角色实例
     * @return JsonResponse
     */
    public function execute(Role $role): JsonResponse
    {
        try {
            // 检查角色是否被用户使用
            if ($role->users()->exists()) {
                return $this->error('角色正在被用户使用，无法删除', 422);
            }

            $roleName = $role->name;
            $roleId = $role->id;

            // 删除角色（会自动删除角色权限关联）
            $role->delete();

            $this->log('delete_role', ['role_id' => $roleId, 'role_name' => $roleName]);

            return $this->success(null, '角色删除成功');

        } catch (\Throwable $e) {
            return $this->handleException($e, 'DeleteRole');
        }
    }
}
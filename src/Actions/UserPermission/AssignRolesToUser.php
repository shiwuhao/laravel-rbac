<?php

namespace Rbac\Actions\UserPermission;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:assign-roles', '分配角色给用户')]
class AssignRolesToUser extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:rbac_roles,id',
            'replace' => 'sometimes|boolean',
        ];
    }

    /**
     * 分配用户角色
     *
     * @return Model
     * @throws \Exception
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($this->context->id());
        
        $roleIds = $this->context->data('role_ids');
        $replace = $this->context->data('replace', false);

        $roles = Role::whereIn('id', $roleIds)->get();

        if ($roles->count() !== count($roleIds)) {
            throw new \Exception('部分角色不存在');
        }

        if ($replace) {
            $user->roles()->sync($roleIds);
        } else {
            $user->roles()->syncWithoutDetaching($roleIds);
        }

        return $user->load(['roles', 'permissions']);
    }
}

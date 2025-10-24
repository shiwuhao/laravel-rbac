<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Collection;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\Role;

#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:revoke-role', '撤销用户角色')]
class RevokeRole extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }

    /**
     * 撤销用户角色
     *
     * @return Collection
     */
    protected function execute(): Collection
    {
        $userModel = config('rbac.models.user');
        $user = $userModel::findOrFail($this->context->id());
        $role = Role::findOrFail($this->context->data('role_id'));

        $user->roles()->detach($role->id);

        return $user->roles;
    }
}

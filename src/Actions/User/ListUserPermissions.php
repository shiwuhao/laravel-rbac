<?php

namespace Rbac\Actions\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:view-permissions', '查看用户权限')]
class ListUserPermissions extends BaseAction
{
    /**
     * 验证规则
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'keyword' => 'sometimes|string',
            'role' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:15|max:100',
        ];
    }

    /**
     * 获取用户权限列表
     *
     * @return LengthAwarePaginator
     */
    protected function execute(): LengthAwarePaginator
    {
        $userModel = config('rbac.models.user');
        $query = $userModel::query()->with(['roles.permissions', 'permissions']);

        if ($this->context->has('keyword')) {
            $keyword = $this->context->data('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($this->context->has('role')) {
            $query->whereHas('roles', function ($q) {
                $q->where('slug', $this->context->data('role'));
            });
        }

        return $query->paginate($this->context->data('per_page', 15));
    }
}

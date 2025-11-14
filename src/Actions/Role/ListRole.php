<?php

namespace Rbac\Actions\Role;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('role:*', '角色管理')]
#[Permission('role:view', '查看角色')]
class ListRole extends BaseAction
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
            'per_page' => 'sometimes|integer|min:15|max:50',
            'guard_name' => 'sometimes|string|max:50',
        ];
    }

    /**
     * 获取角色列表
     *
     * @return LengthAwarePaginator
     */
    protected function execute(): LengthAwarePaginator
    {
        $roleModel = config('rbac.models.role');
        $query = $roleModel::query()->withCount(['permissions', 'users']);

        if ($this->context->has('keyword')) {
            $keyword = $this->context->data('keyword');
            $query->where('name', 'like', "%{$keyword}%");
        }

        if ($this->context->has('guard_name')) {
            $query->where('guard_name', $this->context->data('guard_name'));
        }

        return $query->paginate($this->context->data('per_page', 15));
    }
}

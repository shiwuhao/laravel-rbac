<?php

namespace Rbac\Actions\Permission;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:view', '查看权限')]
class ListPermission extends BaseAction
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
            'resource' => 'sometimes|string|max:100',
            'action' => 'sometimes|string|max:50',
            'guard_name' => 'sometimes|string|max:50',
            'per_page' => 'sometimes|integer|min:15|max:50',
        ];
    }

    /**
     * 获取权限列表
     *
     * @return LengthAwarePaginator
     */
    protected function execute(): LengthAwarePaginator
    {
        $permissionModel = config('rbac.models.permission');
        $query = $permissionModel::query()->withCount(['roles', 'users']);

        if ($this->context->has('keyword')) {
            $keyword = $this->context->data('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('slug', 'like', "%{$keyword}%");
            });
        }

        if ($this->context->has('resource')) {
            $query->where('resource', $this->context->data('resource'));
        }

        if ($this->context->has('action')) {
            $query->where('action', $this->context->data('action'));
        }

        if ($this->context->has('guard_name')) {
            $query->where('guard_name', $this->context->data('guard_name'));
        }

        return $query->paginate($this->context->data('per_page', 15));
    }
}

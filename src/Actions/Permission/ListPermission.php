<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;

/**
 * 获取权限列表（支持分页和树形展示）
 *
 * @example
 * ListPermission::handle([
 *     'keyword' => 'article',
 *     'resource' => 'article',
 *     'action' => 'view',
 *     'guard_name' => 'web',
 *     'format' => 'tree',
 *     'per_page' => 20,
 * ]);
 */
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
            'format' => 'sometimes|string|in:list,tree',
        ];
    }

    /**
     * 获取权限列表
     */
    protected function execute(): mixed
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

        // 树形展示
        if ($this->context->data('format', 'list') === 'tree') {
            $permissions = $query->orderBy('resource')->orderBy('action')->get();

            $grouped = $permissions->groupBy('resource');

            $tree = $grouped->map(function ($items, $resource) {
                return [
                    'key' => $resource ?: 'Unknown',
                    'title' => $resource ?: '未分类资源',
                    'children' => $items->map(function ($p) {
                        return [
                            'key' => $p->id,
                            'title' => $p->name,
                            'slug' => $p->slug,
                            'action' => $p->action,
                            'guard_name' => $p->guard_name,
                            'roles_count' => $p->roles_count ?? 0,
                            'users_count' => $p->users_count ?? 0,
                            'is_instance' => ! empty($p->resource_type) && ! empty($p->resource_id),
                            'resource_type' => $p->resource_type,
                            'resource_id' => $p->resource_id,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            return $tree;
        }

        // 默认分页列表
        return $query->paginate($this->context->data('per_page', 15));
    }
}

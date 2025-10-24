<?php

namespace Rbac\Actions\DataScope;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;
use Rbac\Models\DataScope;

#[PermissionGroup('data-scope:*', '数据范围管理')]
#[Permission('data-scope:view', '查看数据范围')]
class ListDataScope extends BaseAction
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
            'type' => 'sometimes|string|in:all,custom,department,department_and_sub,only_self',
            'is_active' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:15|max:100',
        ];
    }

    /**
     * 获取数据范围列表
     *
     * @return LengthAwarePaginator
     */
    protected function execute(): LengthAwarePaginator
    {
        $query = DataScope::query();

        if ($this->context->has('keyword')) {
            $keyword = $this->context->data('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($this->context->has('type')) {
            $query->where('type', $this->context->data('type'));
        }

        if ($this->context->has('is_active')) {
            $query->where('is_active', $this->context->data('is_active'));
        }

        return $query->paginate($this->context->data('per_page', 15));
    }
}

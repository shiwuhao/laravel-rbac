<?php

namespace Rbac\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface DataScopeContract
{
    /**
     * 用户关联
     */
    public function users(): BelongsToMany;

    /**
     * 应用数据范围过滤
     */
    public function applyScope(Builder $query, $user): Builder;

    /**
     * 检查用户是否可以访问模型
     */
    public function canAccess(Model $model, $user): bool;
}
<?php

namespace Rbac\Traits;

use Illuminate\Database\Eloquent\Builder;
use Rbac\Scopes\DataScopeGlobal;

/**
 * 为模型启用数据范围全局作用域
 */
trait HasDataScopeScope
{
    /**
     * 启动 Trait，注册全局作用域
     */
    protected static function bootHasDataScopeScope(): void
    {
        static::addGlobalScope(new DataScopeGlobal());
    }

    /**
     * 查询时移除数据范围全局作用域
     */
    public function scopeWithoutDataScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(DataScopeGlobal::class);
    }
}

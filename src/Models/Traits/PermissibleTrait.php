<?php

namespace Shiwuhao\Rbac\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Shiwuhao\Rbac\Models\Permission;

/**
 * Trait RoleModelTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait PermissibleTrait
{
    /**
     * booted
     */
    protected static function booted()
    {
        self::saved(function ($model) {
            $model->permission ? $model->permission->save() : $model->permission()->create();
        });
        self::deleted(function ($model) {
            $model->permission && $model->permission->delete();
        });
    }

    /**
     * 获取操作对应权限节点
     * @return MorphOne
     */
    public function permission(): MorphOne
    {
        return $this->morphOne(Permission::class, 'permissible');
    }

    /**
     * alias
     * @return string
     */
    public function getAliasAttribute(): string
    {
        return '';
    }
}

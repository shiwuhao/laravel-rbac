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
     * @var string[]
     */
    protected $appends = [
        'alias'
    ];

    /**
     * booted
     */
    protected static function booted()
    {
        self::created(function ($model) {
            $model->permission()->save(new Permission());
        });
        self::updated(function ($model) {
            $model->permission()->fill($model->toArray())->save();
        });
        self::deleted(function ($model) {
            $model->permission->delete();
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
}

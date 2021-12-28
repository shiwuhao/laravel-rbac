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
        self::created(function ($action) {
            $action->permission()->save(new Permission());
        });
        self::updated(function ($action) {
            $action->permission->fill($action->toArray())->save();
        });
        self::deleted(function ($action) {
            $action->permission->delete();
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

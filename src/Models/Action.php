<?php

namespace Shiwuhao\Rbac\Models;


use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    /**
     * Role constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('rbac.table.action'));
    }

    /**
     * 获取操作对应权限节点
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function permission(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Permission::class, 'permissible');
    }
}

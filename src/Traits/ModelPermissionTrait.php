<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;


use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 *
 * Trait ModelPermissionTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait ModelPermissionTrait
{
    /**
     * 模型授权 获取指定模型所有角色
     * 多对多多态关联
     * @return MorphToMany
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(config('rbac.model.role'), 'modelable');
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:43 PM
 */

namespace Shiwuhao\Rbac\Traits;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait PermissionTrait
{
    /**
     * 获取拥有此节点的角色
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.permission'),
            config('rbac.table.permission_role'),
            config('rbac.foreign_key.permission'),
            config('rbac.foreign_key.role'));
    }
}

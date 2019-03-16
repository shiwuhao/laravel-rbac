<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait RoleTrait
{
    /**
     * 获取角色下的用户
     * 用户 角色 多对多关联
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.user'),
            config('rbac.table.role_user'),
            config('rbac.foreign_key.role'),
            config('rbac.foreign_key.user'));
    }

    /**
     * 获取角色小的权限
     * 权限 角色 多对多关联
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.permission'),
            config('rbac.table.permission_role'),
            config('rbac.foreign_key.role'),
            config('rbac.foreign_key.permission'));
    }

    /**
     * 将多个权限附加到当前角色
     * @param $permissions
     */
    public function attachPermissions($permissions)
    {
        $this->permissions()->attach($permissions);
    }

    /**
     * 从当前角色分离多个权限
     * @param null $permissions
     * @return int
     */
    public function detachPermissions($permissions = null)
    {
        if (!$permissions) $permissions = $this->permissions()->get();

        return $this->permissions()->detach($permissions);
    }

    /**
     * 从当前角色同步多个权限
     * @param $permissions
     * @return array
     */
    public function syncPermissions($permissions)
    {
        return $this->permissions()->sync($permissions);
    }
}

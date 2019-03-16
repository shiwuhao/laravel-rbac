<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Shiwuhao\Rbac\Traits\BaseTrait;

trait RoleTrait
{
    use BaseTrait;

    /**
     * 获取角色下的用户
     * 用户 角色 多对多关联
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('rbac.models.user'), 'role_user', 'role_id', 'user_id');
    }

    /**
     * 获取角色小的权限
     * 权限 角色 多对多关联
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('rbac.models.permission'), 'permission_role', 'role_id', 'permission_id');
    }

    /**
     * 将多个权限附加到当前角色
     * @param $permissions
     */
    public function attachPermissions($permissions): void
    {
        $ids = $this->parseIds($permissions);

        foreach ($ids as $id) {
            $this->permissions()->attach($id);
        }
    }

    /**
     * 从当前角色分离多个权限
     * @param null $permissions
     */
    public function detachPermissions($permissions = null): void
    {
        if (!$permissions) $permissions = $this->permissions()->get();

        $ids = $this->parseIds($permissions);

        foreach ($ids as $id) {
            $this->permissions()->detach($id);
        }
    }
}

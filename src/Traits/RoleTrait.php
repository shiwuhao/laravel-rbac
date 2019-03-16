<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

trait RoleTrait
{
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

    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param  mixed $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            return [$value->{$this->getKey()}];
        }

        if ($value instanceof Collection) {
            return $value->pluck($this->getKey())->all();
        }

        if ($value instanceof BaseCollection) {
            return $value->toArray();
        }

        return (array)$value;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Shiwuhao\Rbac\Models\Permission;
use Shiwuhao\Rbac\Models\Role;

/**
 * Trait UserTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait UserTrait
{
    use BaseTrait;

    /**
     * 获取用户拥有的角色
     * 用户 角色 多对多关联
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.role'),
            config('rbac.table.role_user'),
            config('rbac.foreign_key.user'),
            config('rbac.foreign_key.role'));
    }

    /**
     * 获取用户拥有的权限节点
     * @return BelongsToMany
     */
    public function permissions()
    {
        return $this->roles()->with('permissions');
    }

    /**
     * 检测用户是否含有某个或多个角色
     * @param string|array $roles
     * @param bool $requireAll
     * @return bool
     */
    public function hasRole($roles, $requireAll = false)
    {
        $delimiter = config('rbac.delimiter', '|');
        $roles = is_array($roles) ? $roles : explode($delimiter, trim($roles, $delimiter));
        $userRoleNames = $this->roles()->get()->pluck('name');

        foreach ($roles as $name) {
            $has = $userRoleNames->contains($name);

            if ($requireAll == true && $has == false) {
                return false;
            }

            if ($requireAll == false && $has == true) {
                return true;
            }
        }

        return $requireAll;
    }

    /**
     * 检测用户是否含有某个或多个节点
     * @param string|array $permissions
     * @param bool $requireAll
     * @return bool
     */
    public function hasPermission($permissions, $requireAll = false)
    {
        $delimiter = config('rbac.delimiter', '|');
        $permissions = is_array($permissions) ? $permissions : explode($delimiter, trim($permissions, $delimiter));
        $userPermissionNames = $this->permissions()->get()->pluck('permissions')->collapse()->pluck('name')->unique();
        foreach ($permissions as $permission) {
            $has = $userPermissionNames->contains($permission);

            if ($requireAll == true && $has == false) {
                return false;
            }

            if ($requireAll == false && $has == true) {
                return true;
            }
        }

        return $requireAll;
    }

    /**
     * 将多个角色附加到当前用户
     * @param $roles
     */
    public function attachRoles($roles)
    {
        $this->roles()->attach($roles);
    }

    /**
     * 从当前用户分离多个角色
     * @param $roles
     * @return int
     */
    public function detachRoles($roles)
    {
        return $this->roles()->detach($roles);
    }

    /**
     * 从当前用户同步多个角色
     * @param $roles
     * @return array
     */
    public function syncRoles($roles)
    {
        return $this->roles()->sync($roles);
    }

}

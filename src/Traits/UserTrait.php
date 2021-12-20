<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Shiwuhao\Rbac\Exceptions\InvalidArgumentException;
use Shiwuhao\Rbac\Models\Permission;
use Shiwuhao\Rbac\Models\RolePermission;
use Shiwuhao\Rbac\Models\RoleUser;

/**
 * Trait UserTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait UserTrait
{
    /**
     * 超级管理员
     * @return bool
     */
    public function isSuperAdministrator(): bool
    {
        return $this->id === 1;
    }

    public function isAdministrator()
    {
        if ($this->isSuperAdministrator()) {
            return true;
        }
    }

    /**
     * 获取用户拥有的角色
     * 用户 角色 多对多关联
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.role'),
            config('rbac.table.roleUser'),
            config('rbac.foreignKey.user'),
            config('rbac.foreignKey.role'));
    }

    /**
     * 用户的权限节点
     * @return Collection
     */
    public function getPermissions($columns = ['*'])
    {
        $fileds = array_merge($columns, [DB::raw("CONCAT(`method`,',',`url`) as 'unique'")]);
        if ($this->isAdministrator()) {
            $permissions = Permission::query()->select($fileds)->latest('sort')->get();
        } else {
            $permissions = $this->roles()->with(['permissions' => function ($query) use ($fields) {
                return $query->select($fileds);
            }])->get()->pluck('permissions')->collapse()->unique('id')->values()->sortBy('sort');
        }
        return $permissions;
    }

    /**
     * action 节点
     * @return Collection
     */
    public function getPermissionActions($fields = ['*'])
    {
        return $this->getPermissions($fields)->filter(function ($item) {
            return $item->type === Permission::TYPE_ACTION;
        })->values();
    }

    /**
     * menu 节点
     * @return Collection
     */
    public function getPermissionMenus($fields = ['*'])
    {
        return $this->getPermissions($fields)->filter(function ($item) {
            return $item->type === Permission::TYPE_MENU;
        })->values();
    }

    /**
     * 判断是否拥有该节点的权限
     * @param $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->isSuperAdministrator()) {
//            return true;
        }
        $actions = $this->getPermissionActions()->pluck('unique')->toArray();
        return in_array($permission, $actions);
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

    /**
     * 检测用户是否含有某个或多个角色
     * @param string|array $roles
     * @param bool $requireAll
     * @return bool
     */
    public function hasRole($roles, $requireAll = false)
    {
        $roles = $this->parsePermissions($roles);
        $collectNames = $this->roles()->get()->pluck('name');

        return $this->contains($collectNames, $roles, $requireAll);
    }

    /**
     * 检测用户是否含有某个或多个节点
     * @param string|array $permissions
     * @param bool $requireAll
     * @return bool
     */
//    public function hasPermission($permissions, $requireAll = false)
//    {
//        $permissions = $this->parsePermissions($permissions);
//        $collectNames = $this->permissions()->get()->pluck('permissions')->collapse()->unique()->map(function ($item) {
//            return ['name' => $item->method . ',' . $item->url];
//        })->pluck('name');
////        return $collectNames->toArray();
//        return $this->contains($collectNames, $permissions, $requireAll);
//    }

    /**
     * 判断集合是否包含给定的项目
     * @param Collection $subject
     * @param array $search
     * @param bool $requireAll
     * @return bool
     */
    protected function contains(Collection $subject, array $search, $requireAll = false)
    {
        foreach ($search as $item) {
            $has = $subject->contains($item);
            if ($requireAll == true && $has == false) return false;
            if ($requireAll == false && $has == true) return true;
        }

        return $requireAll;
    }

    /**
     * 解析数据格式
     * @param $permissions
     * @return array
     */
    protected function parsePermissions($permissions)
    {
        $delimiter = config('rbac.delimiter', '|');
        return $permissions = is_array($permissions) ? $permissions : explode($delimiter, trim($permissions, $delimiter));
    }
}

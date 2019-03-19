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
use Shiwuhao\Rbac\Exceptions\InvalidArgumentException;

/**
 * Trait UserTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait UserTrait
{
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
     * 获取用户拥有的权限节点
     * @return BelongsToMany
     */
    public function permissions()
    {
        return $this->roles()->with('permissions');
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
    public function hasPermission($permissions, $requireAll = false)
    {
        $permissions = $this->parsePermissions($permissions);
        $collectNames = $this->permissions()->get()->pluck('permissions')->collapse()->pluck('name')->unique();

        return $this->contains($collectNames, $permissions, $requireAll);
    }

    /**
     * 检测用户是否含有某个或多个分类模型节点
     * @param $related
     * @param $modelIds
     * @param bool $requireAll
     * @return bool
     * @throws InvalidArgumentException
     */
    public function hasPermissionModel($related, $modelIds, $requireAll = false)
    {
        $permissionModelConfig = config('rbac.permissionModel');
        $related = strpos($related, '\\') === false ? $related : $permissionModelConfig[$related];
        if (!in_array($related, $permissionModelConfig)) {
            throw new InvalidArgumentException("method $related noe exists");
        }
        $modelIds = $this->parsePermissions($modelIds);
        $collectIds = $this->roles()->with($related)->get()->pluck($related)->collapse()->pluck('id')->unique();

        return $this->contains($collectIds, $modelIds, $requireAll);
    }

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

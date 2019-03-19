<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use App\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Shiwuhao\Rbac\Exceptions\InvalidArgumentException;

/**
 * Trait RoleModelTrait
 * @package Shiwuhao\Rbac\Traits
 */
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
            config('rbac.table.roleUser'),
            config('rbac.foreignKey.role'),
            config('rbac.foreignKey.user'));
    }

    /**
     * 获取角色下的权限节点
     * 权限 角色 多对多关联
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.permission'),
            config('rbac.table.permissionRole'),
            config('rbac.foreignKey.role'),
            config('rbac.foreignKey.permission'));
    }

    /**
     * 获取角色下的指定模型的 模型权限
     * 多对多多态关联 反向关联
     * @param string $modelNamespace
     * @return MorphToMany
     */
    public function modelPermissions(string $modelNamespace): MorphToMany
    {
        return $this->morphedByMany($modelNamespace, 'modelable', config('rbac.table.permissionModel'))->withTimestamps();
    }

    /**
     * 添加某个或多个权限到当前角色
     * @param $permissions
     */
    public function attachPermissions($permissions)
    {
        $this->permissions()->attach($permissions);
    }

    /**
     * 删除某个或多个权限到当前角色
     * @param null $permissions
     * @return int
     */
    public function detachPermissions($permissions = null)
    {
        if (!$permissions) $permissions = $this->permissions()->get();

        return $this->permissions()->detach($permissions);
    }

    /**
     * 同步某个或多个权限到当前角色
     * @param $permissions
     * @return array
     */
    public function syncPermissions($permissions)
    {
        return $this->permissions()->sync($permissions);
    }

    /**
     * 删除某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param $ids
     */
    public function attachPermissionModels(string $related, $ids)
    {
        $related = $this->parsePermissionModels($related);

        $this->$related()->attach($ids);
    }

    /**
     * 添加某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param null $ids
     * @return int
     */
    public function detachPermissionModels(string $related, $ids = null)
    {
        $related = $this->parsePermissionModels($related);

        if (!$ids) $ids = $this->$related()->get();

        return $this->$related()->detach($ids);
    }

    /**
     * 同步某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param $ids
     * @return array
     */
    public function syncPermissionModels(string $related, $ids)
    {
        $related = $this->parsePermissionModels($related);

        return $this->$related()->sync($ids);
    }

    /**
     * 解析 $related 数据格式
     * @param string $related
     */
    protected function parsePermissionModels(string $related)
    {
        $permissionModelConfig = config('rbac.permissionModel');
        $related = strpos($related, '\\') === false ? $related : $permissionModelConfig[$related];
        if (!in_array($related, $permissionModelConfig)) {
            throw new InvalidArgumentException("method $related noe exists");
        }

        return $related;
    }
}

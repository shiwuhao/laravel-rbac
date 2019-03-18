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
            config('rbac.table.role_user'),
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
            config('rbac.table.permission_role'),
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
        return $this->morphedByMany($modelNamespace, 'modelable', config('rbac.table.model_permissions'))->withTimestamps();
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
    public function attachModelPermissions(string $modelNamespace, $ids)
    {
        $this->modelPermissions($modelNamespace)->attach($ids);
    }

    /**
     * 添加某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param null $ids
     * @return int
     */
    public function detachModelPermissions(string $modelNamespace, $ids = null)
    {
        if (!$ids) $ids = $this->modelPermissions($modelNamespace)->get();

        return $this->modelPermissions($modelNamespace)->detach($ids);
    }

    /**
     * 同步某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param $ids
     * @return array
     */
    public function syncModelPermissions(string $modelNamespace, $ids)
    {
        return $this->modelPermissions($modelNamespace)->sync($ids);
    }

}

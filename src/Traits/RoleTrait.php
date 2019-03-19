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
     * @var array
     */
    protected static $methods = [];

    /**
     * boot
     * @throws InvalidArgumentException
     */
    public static function boot()
    {
        parent::boot();
//        static::initPermissionModel();
    }

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
    public function attachPermissionModels(string $methodName, $ids)
    {
        $this->validateMethodByPermissionModels($methodName);
        $this->$methodName()->attach($ids);
    }

    /**
     * 添加某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param null $ids
     * @return int
     */
    public function detachPermissionModels(string $methodName, $ids = null)
    {
        $this->validateMethodByPermissionModels($methodName);

        if (!$ids) $ids = $this->$methodName()->get();

        return $this->$methodName()->detach($ids);
    }

    /**
     * 同步某个或多个模型权限到当前角色
     * @param string $modelNamespace
     * @param $ids
     * @return array
     */
    public function syncPermissionModels(string $methodName, $ids)
    {
        $this->validateMethodByPermissionModels($methodName);

        return $this->$methodName()->sync($ids);
    }

    /**
     * 验证 $methodName 是否存在
     * @param string $methodName
     */
    protected function validateMethodByPermissionModels(string $methodName)
    {
        $permissionModelConfig = config('rbac.permissionModel');
        if (!in_array($methodName, $permissionModelConfig)) {
            throw new InvalidArgumentException("method {$methodName} not exists in {____}");
        }
    }

    /**
     * 初始化模型授权
     */
    protected static function initPermissionModel()
    {
        if ($permissionModel = config('rbac.permissionModel')) {
            foreach ($permissionModel as $methodName => $modelNamespace) {
                self::addMethod($methodName, function ($self) use ($modelNamespace) {
                    return $self->morphedByMany($modelNamespace, 'modelable', config('rbac.table.permissionModel'))->withTimestamps();
                });
            }
        }
    }

    /**
     * @param string $methodName
     * @param callable $methodCallable
     * @throws InvalidArgumentException
     */
    protected static function addMethod(string $methodName, callable $methodCallable)
    {
        if (!is_callable($methodCallable)) {
            throw new InvalidArgumentException('Second param must be callable');
        }

        self::$methods[$methodName] = $methodCallable;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (isset(self::$methods[$method])) {
            array_unshift($parameters, $this);
            return call_user_func_array(self::$methods[$method], $parameters);
        }

        return parent::__call($method, $parameters);
    }
}

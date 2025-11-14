<?php

namespace Rbac\Support;

use Illuminate\Database\Eloquent\Model;
use Rbac\Actions\User\AssignInstancePermissionToUser;
use Rbac\Actions\User\RevokeInstancePermissionFromUser;
use Rbac\Actions\Role\AssignInstancePermissionToRole;
use Rbac\Actions\Role\RevokeInstancePermissionFromRole;

/**
 * 实例权限辅助类
 * 
 * 提供便捷的实例权限管理方法
 * 内部委托给 Action 执行写操作，保证业务逻辑一致性
 */
class InstancePermissionHelper
{
    /**
     * 为模型实例生成权限标识
     * 
     * @param string $action 操作类型（如 view, edit）
     * @param Model $model 模型实例
     * @return string
     */
    public static function generatePermissionSlug(string $action, Model $model): string
    {
        $resource = strtolower(class_basename($model));
        return "{$resource}:{$action}";
    }

    /**
     * 检查用户是否有模型实例的权限
     * 
     * @param mixed $user 用户实例
     * @param string $action 操作类型
     * @param Model $model 模型实例
     * @return bool
     * 
     * @example InstancePermissionHelper::can($user, 'view', $report)
     */
    public static function can($user, string $action, Model $model): bool
    {
        if (!method_exists($user, 'hasPermission')) {
            return false;
        }

        $permissionSlug = self::generatePermissionSlug($action, $model);
        $resourceType = get_class($model);
        $resourceId = $model->getKey();

        return $user->hasPermission($permissionSlug, $resourceType, $resourceId);
    }

    /**
     * 为用户授予模型实例的权限
     * 
     * @param mixed $user 用户实例
     * @param string $action 操作类型
     * @param Model $model 模型实例
     * @return void
     * 
     * @example InstancePermissionHelper::grant($user, 'view', $report)
     */
    public static function grant($user, string $action, Model $model): void
    {
        $permissionSlug = self::generatePermissionSlug($action, $model);
        $resourceType = get_class($model);
        $resourceId = $model->getKey();

        // 委托给 Action 执行
        AssignInstancePermissionToUser::handle([
            'user_id' => $user->getKey(),
            'permission_slug' => $permissionSlug,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }

    /**
     * 撤销用户对模型实例的权限
     * 
     * @param mixed $user 用户实例
     * @param string $action 操作类型
     * @param Model $model 模型实例
     * @return void
     */
    public static function revoke($user, string $action, Model $model): void
    {
        $permissionSlug = self::generatePermissionSlug($action, $model);
        $resourceType = get_class($model);
        $resourceId = $model->getKey();

        // 委托给 Action 执行
        RevokeInstancePermissionFromUser::handle([
            'user_id' => $user->getKey(),
            'permission_slug' => $permissionSlug,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }

    /**
     * 批量授予权限
     * 
     * @param mixed $user 用户实例
     * @param array $actions 操作类型数组
     * @param Model $model 模型实例
     * @return void
     * 
     * @example InstancePermissionHelper::grantMany($user, ['view', 'edit'], $report)
     */
    public static function grantMany($user, array $actions, Model $model): void
    {
        $permissions = collect($actions)->map(function ($action) use ($model) {
            return [
                'slug' => self::generatePermissionSlug($action, $model),
                'resource_type' => get_class($model),
                'resource_id' => $model->getKey(),
            ];
        })->toArray();

        AssignInstancePermissionToUser::handle([
            'user_id' => $user->getKey(),
            'permissions' => $permissions,
        ]);
    }

    /**
     * 批量授予多个资源的权限
     * 
     * @param mixed $user 用户实例
     * @param array $items [['model' => $model, 'actions' => ['view', 'edit']], ...]
     * @return void
     * 
     * @example 
     * InstancePermissionHelper::grantManyResources($user, [
     *     ['model' => $menu1, 'actions' => ['access']],
     *     ['model' => $menu2, 'actions' => ['access']],
     * ]);
     */
    public static function grantManyResources($user, array $items): void
    {
        $permissions = [];

        foreach ($items as $item) {
            $model = $item['model'];
            $actions = $item['actions'] ?? [];

            foreach ($actions as $action) {
                $permissions[] = [
                    'slug' => self::generatePermissionSlug($action, $model),
                    'resource_type' => get_class($model),
                    'resource_id' => $model->getKey(),
                ];
            }
        }

        if (!empty($permissions)) {
            AssignInstancePermissionToUser::handle([
                'user_id' => $user->getKey(),
                'permissions' => $permissions,
            ]);
        }
    }

    /**
     * 为角色授予模型实例的权限
     * 
     * @param mixed $role 角色实例
     * @param string $action 操作类型
     * @param Model $model 模型实例
     * @return void
     * 
     * @example InstancePermissionHelper::grantToRole($role, 'view', $report)
     */
    public static function grantToRole($role, string $action, Model $model): void
    {
        $permissionSlug = self::generatePermissionSlug($action, $model);
        $resourceType = get_class($model);
        $resourceId = $model->getKey();

        // 委托给 Action 执行
        AssignInstancePermissionToRole::handle([
            'role_id' => $role->getKey(),
            'permission_slug' => $permissionSlug,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }

    /**
     * 撤销角色对模型实例的权限
     * 
     * @param mixed $role 角色实例
     * @param string $action 操作类型
     * @param Model $model 模型实例
     * @return void
     */
    public static function revokeFromRole($role, string $action, Model $model): void
    {
        $permissionSlug = self::generatePermissionSlug($action, $model);
        $resourceType = get_class($model);
        $resourceId = $model->getKey();

        // 委托给 Action 执行
        RevokeInstancePermissionFromRole::handle([
            'role_id' => $role->getKey(),
            'permission_slug' => $permissionSlug,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }

    /**
     * 为角色批量授予权限
     * 
     * @param mixed $role 角色实例
     * @param array $actions 操作类型数组
     * @param Model $model 模型实例
     * @return void
     */
    public static function grantManyToRole($role, array $actions, Model $model): void
    {
        $permissions = collect($actions)->map(function ($action) use ($model) {
            return [
                'slug' => self::generatePermissionSlug($action, $model),
                'resource_type' => get_class($model),
                'resource_id' => $model->getKey(),
            ];
        })->toArray();

        AssignInstancePermissionToRole::handle([
            'role_id' => $role->getKey(),
            'permissions' => $permissions,
        ]);
    }

    /**
     * 为角色批量授予多个资源的权限
     * 
     * @param mixed $role 角色实例
     * @param array $items [['model' => $model, 'actions' => ['view']], ...]
     * @return void
     */
    public static function grantManyResourcesToRole($role, array $items): void
    {
        $permissions = [];

        foreach ($items as $item) {
            $model = $item['model'];
            $actions = $item['actions'] ?? [];

            foreach ($actions as $action) {
                $permissions[] = [
                    'slug' => self::generatePermissionSlug($action, $model),
                    'resource_type' => get_class($model),
                    'resource_id' => $model->getKey(),
                ];
            }
        }

        if (!empty($permissions)) {
            AssignInstancePermissionToRole::handle([
                'role_id' => $role->getKey(),
                'permissions' => $permissions,
            ]);
        }
    }
}

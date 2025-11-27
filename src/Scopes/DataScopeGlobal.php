<?php

namespace Rbac\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Collection;

/**
 * 全局数据范围作用域
 * 将有效的数据范围自动应用到启用该作用域的模型查询中
 */
class DataScopeGlobal implements Scope
{
    /**
     * 应用全局作用域
     */
    public function apply(Builder $builder, Model $model): void
    {
        // 控制台或无请求上下文时跳过
        if (app()->runningInConsole()) {
            return;
        }

        $request = request();
        $user = $request->user();
        if (!$user) {
            return;
        }

        // 超级管理员跳过
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        // 当前权限标识（由中间件写入）
        $permissionSlug = $request->attributes->get('rbac.current_permission');
        if (!$permissionSlug && app()->bound('rbac.current_permission')) {
            $permissionSlug = app('rbac.current_permission');
        }

        if (!$permissionSlug) {
            // 未设置当前权限时不应用数据范围，避免误过滤
            return;
        }

        // 计算有效数据范围集合
        $scopes = $this->effectiveScopesForPermission($user, (string) $permissionSlug);

        // 空范围策略
        $emptyStrategy = config('rbac.data_scope.empty_strategy', 'deny');
        if ($scopes->isEmpty()) {
            if ($emptyStrategy === 'ignore') {
                return; // 不应用任何范围
            }
            // 默认更安全：空结果
            $builder->whereRaw('1 = 0');
            return;
        }

        // 组合模式：and（交集）| or（并集）
        $mode = config('rbac.data_scope.mode', 'and');
        if ($mode === 'or') {
            $builder->where(function ($outer) use ($scopes, $user) {
                foreach ($scopes as $scope) {
                    $outer->orWhere(function ($q) use ($scope, $user) {
                        $scope->applyScope($q, $user);
                    });
                }
            });
            return;
        }

        // and：依次应用每个范围（AND 过滤）
        foreach ($scopes as $scope) {
            $scope->applyScope($builder, $user);
        }
    }

    /**
     * 计算某用户在某权限下的有效数据范围集合
     */
    protected function effectiveScopesForPermission($user, string $permissionSlug): Collection
    {
        // 请求级缓存，避免重复计算
        $cacheKey = 'rbac.effective_scopes.' . $permissionSlug . '.' . $user->getKey();
        $request = request();
        if ($request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $dataScopeModel = config('rbac.models.data_scope');

        // 用户直授范围
        $userScopes = $dataScopeModel::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->getKey());
        })->get();
        // 角色继承范围
        $roleScopes = collect();
        if (method_exists($user, 'roles')) {
            $roles = $user->roles()->where('enabled', true)->with('dataScopes')->get();
            $roleScopes = $roles->flatMap->dataScopes;
        }
        // 并集（用户直授 ∪ 角色继承）
        $effective = $userScopes->merge($roleScopes)->unique('id')->values();

        $request->attributes->set($cacheKey, $effective);
        return $effective;
    }
}

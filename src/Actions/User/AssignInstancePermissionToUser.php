<?php

namespace Rbac\Actions\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;

/**
 * 为用户分配实例权限
 *
 * 支持单个或批量分配，自动处理权限创建和去重
 *
 * @example 单个分配
 * AssignInstancePermissionToUser::handle([
 *     'user_id' => 1,
 *     'permission_slug' => 'report:view',
 *     'resource_type' => 'App\Models\Report',
 *     'resource_id' => 123,
 * ]);
 * @example 批量分配
 * AssignInstancePermissionToUser::handle([
 *     'user_id' => 1,
 *     'permissions' => [
 *         ['slug' => 'menu:access', 'resource_type' => 'App\Models\Menu', 'resource_id' => 1],
 *         ['slug' => 'menu:access', 'resource_type' => 'App\Models\Menu', 'resource_id' => 2],
 *     ]
 * ]);
 */
#[Permission('user:assign-instance-permissions', '分配实例权限给用户')]
class AssignInstancePermissionToUser extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $userTable = config('rbac.tables.users', 'users');

        return [
            'user_id' => "required|exists:{$userTable},id",

            // 单个权限参数（兼容旧接口）
            'permission_slug' => 'required_without:permissions|string',
            'resource_type' => 'required_without:permissions|string',
            'resource_id' => 'required_without:permissions|integer',

            // 批量权限参数
            'permissions' => 'required_without:permission_slug|array|min:1',
            'permissions.*.slug' => 'required|string',
            'permissions.*.resource_type' => 'required|string',
            'permissions.*.resource_id' => 'required|integer',
        ];
    }

    /**
     * 执行分配
     */
    protected function execute(): Model
    {
        $userModel = config('rbac.models.user');
        $permissionModel = config('rbac.models.permission');

        $user = $userModel::findOrFail($this->context->data('user_id'));

        // 标准化为批量格式
        $requestedPermissions = $this->normalizePermissions();

        return DB::transaction(function () use ($user, $permissionModel, $requestedPermissions) {
            // 1. 提取所有唯一的权限标识
            $uniqueSlugs = collect($requestedPermissions)->pluck('slug')->unique()->values();

            // 2. 批量查询基础权限（无 resource_type/resource_id）
            $basePermissions = $permissionModel::whereIn('slug', $uniqueSlugs)
                ->whereNull('resource_type')
                ->whereNull('resource_id')
                ->get()
                ->keyBy('slug');

            // 3. 构建需要查询的实例权限条件
            $instanceConditions = collect($requestedPermissions)->map(function ($item) {
                return [
                    'slug' => $item['slug'],
                    'resource_type' => $item['resource_type'],
                    'resource_id' => $item['resource_id'],
                ];
            })->unique(function ($item) {
                return $item['slug'].'|'.$item['resource_type'].'|'.$item['resource_id'];
            })->values();

            // 4. 批量查询已存在的实例权限
            $existingPermissions = $this->batchFindInstancePermissions(
                $permissionModel,
                $instanceConditions
            );

            // 5. 找出需要创建的权限
            $toCreate = [];
            foreach ($instanceConditions as $condition) {
                $key = $condition['slug'].'|'.$condition['resource_type'].'|'.$condition['resource_id'];

                if (! isset($existingPermissions[$key])) {
                    $basePermission = $basePermissions[$condition['slug']] ?? null;

                    if (! $basePermission) {
                        continue; // 跳过无效的权限标识
                    }

                    $toCreate[] = [
                        'name' => $basePermission->name.' #'.$condition['resource_id'],
                        'slug' => $condition['slug'],
                        'resource' => $basePermission->resource,
                        'action' => $basePermission->action,
                        'resource_type' => $condition['resource_type'],
                        'resource_id' => $condition['resource_id'],
                        'guard_name' => $basePermission->guard_name,
                        'description' => "实例权限：{$condition['resource_type']}#{$condition['resource_id']}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // 6. 批量创建新权限
            if (! empty($toCreate)) {
                $permissionModel::insert($toCreate);

                // 重新查询刚创建的权限
                $newlyCreated = $this->batchFindInstancePermissions(
                    $permissionModel,
                    $instanceConditions
                );
                $existingPermissions = array_merge($existingPermissions, $newlyCreated);
            }

            // 7. 批量分配权限给用户
            $permissionIds = collect($existingPermissions)->pluck('id')->unique()->values();
            $user->directPermissions()->syncWithoutDetaching($permissionIds);

            // 8. 清理缓存
            if (method_exists($user, 'forgetCachedPermissions')) {
                $user->forgetCachedPermissions();
            }

            return $user->load('directPermissions');
        });
    }

    /**
     * 标准化权限数据为批量格式
     */
    protected function normalizePermissions(): array
    {
        // 如果是批量格式，直接返回
        if ($this->context->has('permissions')) {
            return $this->context->data('permissions');
        }

        // 单个权限转为批量格式
        return [[
            'slug' => $this->context->data('permission_slug'),
            'resource_type' => $this->context->data('resource_type'),
            'resource_id' => $this->context->data('resource_id'),
        ]];
    }

    /**
     * 批量查询实例权限
     */
    protected function batchFindInstancePermissions($permissionModel, $conditions): array
    {
        if ($conditions->isEmpty()) {
            return [];
        }

        // 构建 OR 查询
        $query = $permissionModel::query();

        foreach ($conditions as $condition) {
            $query->orWhere(function ($q) use ($condition) {
                $q->where('slug', $condition['slug'])
                    ->where('resource_type', $condition['resource_type'])
                    ->where('resource_id', $condition['resource_id']);
            });
        }

        $permissions = $query->get();

        // 按组合键索引
        $indexed = [];
        foreach ($permissions as $permission) {
            $key = $permission->slug.'|'.$permission->resource_type.'|'.$permission->resource_id;
            $indexed[$key] = $permission;
        }

        return $indexed;
    }
}

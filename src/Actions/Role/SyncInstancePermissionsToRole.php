<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 同步角色的实例权限（批量）
 *
 * 会移除角色现有的所有实例权限，只保留本次同步的权限
 *
 * @example
 * SyncInstancePermissionsToRole::handle([
 *     'permissions' => [
 *         ['slug' => 'menu:access', 'resource_type' => 'App\\Models\\Menu', 'resource_id' => 1],
 *         ['slug' => 'menu:access', 'resource_type' => 'App\\Models\\Menu', 'resource_id' => 2],
 *     ]
 * ], $roleId);
 */
#[Permission('role:sync-instance-permissions', '同步角色实例权限')]
class SyncInstancePermissionsToRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        return [
            'permissions' => 'required|array|min:1',
            'permissions.*.slug' => 'required|string',
            'permissions.*.resource_type' => 'required|string',
            'permissions.*.resource_id' => 'required|integer',
        ];
    }

    /**
     * 执行同步
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');

        $role = $roleModel::findOrFail($this->context->id());
        $permissions = $this->context->data('permissions');

        return DB::transaction(function () use ($role, $permissionModel, $permissions) {
            // 1. 提取所有唯一的权限标识
            $uniqueSlugs = collect($permissions)->pluck('slug')->unique()->values();

            // 2. 批量查询基础权限（无 resource_type/resource_id）
            $basePermissions = $permissionModel::whereIn('slug', $uniqueSlugs)
                ->whereNull('resource_type')
                ->whereNull('resource_id')
                ->get()
                ->keyBy('slug');

            // 3. 构建需要查询的实例权限条件（去重）
            $instanceConditions = collect($permissions)->map(function ($item) {
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
            $missingBasePermissions = [];
            foreach ($instanceConditions as $condition) {
                $key = $condition['slug'].'|'.$condition['resource_type'].'|'.$condition['resource_id'];

                if (!isset($existingPermissions[$key])) {
                    $basePermission = $basePermissions[$condition['slug']] ?? null;

                    if (!$basePermission) {
                        $missingBasePermissions[] = $condition['slug'];
                        continue;
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

            // 如果有缺失的基础权限，抛出异常
            if (!empty($missingBasePermissions)) {
                throw new \Exception(
                    '以下基础权限不存在，无法创建实例权限：'.implode(', ', array_unique($missingBasePermissions)).
                    '。请先创建基础权限（resource_type 和 resource_id 为 NULL 的权限记录）。'
                );
            }

            // 6. 批量创建新权限
            if (!empty($toCreate)) {
                $permissionModel::insert($toCreate);

                // 重新查询刚创建的权限
                $newlyCreated = $this->batchFindInstancePermissions(
                    $permissionModel,
                    $instanceConditions
                );
                $existingPermissions = array_merge($existingPermissions, $newlyCreated);
            }

            // 7. 获取角色当前所有实例权限的 ID
            $permissionsTable = config('rbac.tables.permissions', 'permissions');
            $currentInstancePermissionIds = $role->permissions()
                ->whereNotNull("{$permissionsTable}.resource_type")
                ->whereNotNull("{$permissionsTable}.resource_id")
                ->pluck("{$permissionsTable}.id");

            // 8. 同步权限给角色（移除旧的实例权限，只保留新的）
            $newPermissionIds = collect($existingPermissions)->pluck('id')->unique()->values();

            // 先移除旧的实例权限
            $role->permissions()->detach($currentInstancePermissionIds);

            // 再添加新的实例权限
            $role->permissions()->syncWithoutDetaching($newPermissionIds);

            // 9. 清除关联用户的缓存
            $role->users()->each(function ($user) {
                if (method_exists($user, 'forgetCachedPermissions')) {
                    $user->forgetCachedPermissions();
                }
            });

            return $role->load('permissions');
        });
    }

    /**
     * 批量查询实例权限
     */
    protected function batchFindInstancePermissions($permissionModel, $conditions): array
    {
        if ($conditions->isEmpty()) {
            return [];
        }

        $query = $permissionModel::query();

        foreach ($conditions as $condition) {
            $query->orWhere(function ($q) use ($condition) {
                $q->where('slug', $condition['slug'])
                    ->where('resource_type', $condition['resource_type'])
                    ->where('resource_id', $condition['resource_id']);
            });
        }

        $permissions = $query->get();

        $indexed = [];
        foreach ($permissions as $permission) {
            $key = $permission->slug . '|' . $permission->resource_type . '|' . $permission->resource_id;
            $indexed[$key] = $permission;
        }

        return $indexed;
    }
}

<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 为角色分配实例权限（批量）
 *
 * @example
 * AssignInstancePermissionToRole::handle([
 *     'permissions' => [
 *         ['slug' => 'menu:access', 'resource_type' => 'App\\Models\\Menu', 'resource_id' => 1],
 *         ['slug' => 'menu:access', 'resource_type' => 'App\\Models\\Menu', 'resource_id' => 2],
 *     ]
 * ], $roleId);
 */
#[Permission('role:assign-instance-permissions', '为角色分配实例权限')]
class AssignInstancePermissionToRole extends BaseAction
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
     * 执行分配
     */
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');

        $role = $roleModel::findOrFail($this->context->id());
        $permissions = $this->context->data('permissions');

        return DB::transaction(function () use ($role, $permissionModel, $permissions) {
            // 标准化 slug 格式（将 . 替换为 :)
            $permissions = collect($permissions)->map(function ($item) {
                $item['slug'] = str_replace('.', ':', $item['slug']);
                return $item;
            })->all();

            $uniqueSlugs = collect($permissions)->pluck('slug')->unique()->values();

            // 查询基础权限（通用权限）
            $basePermissions = $permissionModel::whereIn('slug', $uniqueSlugs)
                ->whereNull('resource_type')
                ->whereNull('resource_id')
                ->get()
                ->keyBy('slug');

            // 检查是否所有 slug 都存在基础权限
            $missingSlugs = $uniqueSlugs->diff($basePermissions->keys());
            if ($missingSlugs->isNotEmpty()) {
                throw new \Exception('以下基础权限不存在: ' . $missingSlugs->implode(', '));
            }

            // 去重实例条件
            $instanceConditions = collect($permissions)->unique(function ($item) {
                return $item['slug'] . '|' . $item['resource_type'] . '|' . $item['resource_id'];
            })->values();

            // 查找已存在的实例权限
            $existingPermissions = $this->batchFindInstancePermissions(
                $permissionModel,
                $instanceConditions
            );

            // 准备需要创建的实例权限
            $toCreate = [];
            foreach ($instanceConditions as $condition) {
                $key = $condition['slug'] . '|' . $condition['resource_type'] . '|' . $condition['resource_id'];

                if (!isset($existingPermissions[$key])) {
                    $basePermission = $basePermissions[$condition['slug']];

                    $toCreate[] = [
                        'name' => $basePermission->name . ' #' . $condition['resource_id'],
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

            // 批量创建实例权限
            if (!empty($toCreate)) {
                $permissionModel::insert($toCreate);

                // 重新查询新创建的权限
                $newlyCreated = $this->batchFindInstancePermissions(
                    $permissionModel,
                    $instanceConditions
                );
                $existingPermissions = array_merge($existingPermissions, $newlyCreated);
            }

            // 分配权限给角色（不移除现有权限）
            $permissionIds = collect($existingPermissions)->pluck('id')->unique()->values();
            $role->permissions()->syncWithoutDetaching($permissionIds);

            // 清除关联用户的缓存
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

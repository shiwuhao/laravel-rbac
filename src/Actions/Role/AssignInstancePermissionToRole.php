<?php

namespace Rbac\Actions\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Contracts\RoleContract;

/**
 * 为角色分配实例权限
 * 
 * 支持单个或批量分配
 * 
 * @example 单个分配
 * AssignInstancePermissionToRole::handle([
 *     'role_id' => 1,
 *     'permission_slug' => 'report:view',
 *     'resource_type' => 'App\Models\Report',
 *     'resource_id' => 123,
 * ]);
 * 
 * @example 批量分配
 * AssignInstancePermissionToRole::handle([
 *     'role_id' => 1,
 *     'permissions' => [
 *         ['slug' => 'menu:access', 'resource_type' => 'App\Models\Menu', 'resource_id' => 1],
 *         ['slug' => 'menu:access', 'resource_type' => 'App\Models\Menu', 'resource_id' => 2],
 *     ]
 * ]);
 */
#[Permission('role:assign-instance-permissions', '为角色分配实例权限')]
class AssignInstancePermissionToRole extends BaseAction
{
    /**
     * 验证规则
     */
    protected function rules(): array
    {
        $roleTable = config('rbac.tables.roles');
        
        return [
            'role_id' => "required|exists:{$roleTable},id",
            
            // 单个权限参数
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
    protected function execute(): RoleContract&Model
    {
        $roleModel = config('rbac.models.role');
        $permissionModel = config('rbac.models.permission');
        
        $role = $roleModel::findOrFail($this->context->data('role_id'));
        $requestedPermissions = $this->normalizePermissions();

        return DB::transaction(function () use ($role, $permissionModel, $requestedPermissions) {
            $uniqueSlugs = collect($requestedPermissions)->pluck('slug')->unique()->values();
            
            $basePermissions = $permissionModel::whereIn('slug', $uniqueSlugs)
                ->whereNull('resource_type')
                ->whereNull('resource_id')
                ->get()
                ->keyBy('slug');

            $instanceConditions = collect($requestedPermissions)->map(function ($item) {
                return [
                    'slug' => $item['slug'],
                    'resource_type' => $item['resource_type'],
                    'resource_id' => $item['resource_id'],
                ];
            })->unique(function ($item) {
                return $item['slug'] . '|' . $item['resource_type'] . '|' . $item['resource_id'];
            })->values();

            $existingPermissions = $this->batchFindInstancePermissions(
                $permissionModel,
                $instanceConditions
            );

            $toCreate = [];
            foreach ($instanceConditions as $condition) {
                $key = $condition['slug'] . '|' . $condition['resource_type'] . '|' . $condition['resource_id'];
                
                if (!isset($existingPermissions[$key])) {
                    $basePermission = $basePermissions[$condition['slug']] ?? null;
                    
                    if (!$basePermission) {
                        continue;
                    }

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

            if (!empty($toCreate)) {
                $permissionModel::insert($toCreate);
                
                $newlyCreated = $this->batchFindInstancePermissions(
                    $permissionModel,
                    $instanceConditions
                );
                $existingPermissions = array_merge($existingPermissions, $newlyCreated);
            }

            $permissionIds = collect($existingPermissions)->pluck('id')->unique()->values();
            $role->permissions()->syncWithoutDetaching($permissionIds);

            return $role->load('permissions');
        });
    }

    /**
     * 标准化权限数据
     */
    protected function normalizePermissions(): array
    {
        if ($this->context->has('permissions')) {
            return $this->context->data('permissions');
        }

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

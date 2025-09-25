<?php

namespace Rbac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * RBAC 门面
 * 
 * @method static \Rbac\Models\Role createRole(string $name, string $slug, ?string $description = null, string|\Rbac\Enums\GuardType $guard = \Rbac\Enums\GuardType::WEB)
 * @method static \Rbac\Models\Permission createPermission(string $name, string $slug, string $resource, string|\Rbac\Enums\ActionType $action, ?string $description = null, string|\Rbac\Enums\GuardType $guard = \Rbac\Enums\GuardType::WEB, ?array $metadata = null)
 * @method static \Rbac\Models\DataScope createDataScope(string $name, string|\Rbac\Enums\DataScopeType $type, ?array $config = null, ?string $description = null)
 * @method static void assignPermissionToRole(\Rbac\Models\Role $role, \Rbac\Models\Permission $permission)
 * @method static void removePermissionFromRole(\Rbac\Models\Role $role, \Rbac\Models\Permission $permission)
 * @method static void assignRoleToUser($user, \Rbac\Models\Role $role)
 * @method static void removeRoleFromUser($user, \Rbac\Models\Role $role)
 * @method static void assignPermissionToUser($user, \Rbac\Models\Permission $permission)
 * @method static void removePermissionFromUser($user, \Rbac\Models\Permission $permission)
 * @method static bool checkUserPermission($user, string $permissionSlug)
 * @method static \Illuminate\Support\Collection getUserPermissions($user)
 * @method static \Illuminate\Support\Collection getRolePermissions(\Rbac\Models\Role $role)
 * @method static \Illuminate\Support\Collection getUserDataScopes($user)
 * @method static \Illuminate\Support\Collection createResourcePermissions(string $resource, array $actions = null, string|\Rbac\Enums\GuardType $guard = \Rbac\Enums\GuardType::WEB)
 * 
 * @see \Rbac\Services\RbacService
 */
class Rbac extends Facade
{
    /**
     * 获取门面访问器
     */
    protected static function getFacadeAccessor(): string
    {
        return 'rbac';
    }
}
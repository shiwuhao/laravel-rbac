<?php

namespace Rbac\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Services\RbacService;
use Rbac\Enums\ActionType;
use Rbac\Enums\DataScopeType;
use Rbac\Enums\GuardType;
use Rbac\Tests\TestCase;

/**
 * @deprecated 从 v2.0 开始，推荐使用 Action 模式
 */

class RbacServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RbacService $rbacService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rbacService = new RbacService();
    }

    public function test_can_create_role()
    {
        $role = $this->rbacService->createRole(
            'Administrator',
            'admin',
            'Admin role',
            'web'
        );

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Administrator', $role->name);
        $this->assertEquals('admin', $role->slug);
        $this->assertEquals('Admin role', $role->description);
        $this->assertEquals('web', $role->guard_name);
    }

    public function test_can_create_permission()
    {
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            'view',
            'Can view users',
            'web',
            ['group' => 'user-management']
        );

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('View Users', $permission->name);
        $this->assertEquals('users.view', $permission->slug);
        $this->assertEquals('users', $permission->resource);
        $this->assertEquals('view', $permission->action);
        $this->assertEquals('Can view users', $permission->description);
        $this->assertEquals('web', $permission->guard_name);
        $this->assertIsArray($permission->metadata);
    }

    public function test_can_create_data_scope()
    {
        $dataScope = $this->rbacService->createDataScope(
            'Department Scope',
            DataScopeType::DEPARTMENT,
            ['department_ids' => [1, 2, 3]],
            'Department level access'
        );

        $this->assertInstanceOf(DataScope::class, $dataScope);
        $this->assertEquals('Department Scope', $dataScope->name);
        $this->assertEquals(DataScopeType::DEPARTMENT, $dataScope->type);
        $this->assertEquals(['department_ids' => [1, 2, 3]], $dataScope->config);
        $this->assertEquals('Department level access', $dataScope->description);
    }

    public function test_can_assign_permission_to_role()
    {
        $role = $this->rbacService->createRole('Admin', 'admin');
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            ActionType::VIEW
        );

        $this->rbacService->assignPermissionToRole($role, $permission);

        $this->assertTrue($role->fresh()->permissions->contains($permission));
    }

    public function test_can_remove_permission_from_role()
    {
        $role = $this->rbacService->createRole('Admin', 'admin');
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            ActionType::VIEW
        );

        $this->rbacService->assignPermissionToRole($role, $permission);
        $this->assertTrue($role->fresh()->permissions->contains($permission));

        $this->rbacService->removePermissionFromRole($role, $permission);
        $this->assertFalse($role->fresh()->permissions->contains($permission));
    }

    public function test_can_sync_permissions_to_role()
    {
        $role = $this->rbacService->createRole('Admin', 'admin');
        
        $permission1 = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            'view'
        );
        
        $permission2 = $this->rbacService->createPermission(
            'Update Users',
            'users.update',
            'users',
            'update'
        );

        $this->rbacService->assignPermissionToRole($role, $permission1);
        $this->rbacService->assignPermissionToRole($role, $permission2);
        $this->assertCount(2, $role->fresh()->permissions);

        $this->rbacService->syncPermissionsToRole($role, [$permission1]);
        $this->assertCount(1, $role->fresh()->permissions);
        $this->assertTrue($role->fresh()->permissions->contains($permission1));
        $this->assertFalse($role->fresh()->permissions->contains($permission2));
    }

    public function test_can_attach_data_scope_to_permission()
    {
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            ActionType::VIEW
        );

        $dataScope = $this->rbacService->createDataScope(
            'Department Scope',
            DataScopeType::DEPARTMENT
        );

        $this->rbacService->attachDataScopeToPermission($permission, $dataScope, 'department_id');

        $this->assertTrue($permission->fresh()->dataScopes->contains($dataScope));
    }

    public function test_can_get_role_by_slug()
    {
        $role = $this->rbacService->createRole('Admin', 'admin');

        $foundRole = $this->rbacService->getRoleBySlug('admin');

        $this->assertInstanceOf(Role::class, $foundRole);
        $this->assertEquals($role->id, $foundRole->id);
    }

    public function test_can_get_permission_by_slug()
    {
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            ActionType::VIEW
        );

        $foundPermission = $this->rbacService->getPermissionBySlug('users.view');

        $this->assertInstanceOf(Permission::class, $foundPermission);
        $this->assertEquals($permission->id, $foundPermission->id);
    }

    public function test_can_delete_role()
    {
        $role = $this->rbacService->createRole('Admin', 'admin');

        $this->assertDatabaseHas('roles', ['slug' => 'admin']);

        $this->rbacService->deleteRole($role);

        $this->assertDatabaseMissing('roles', ['slug' => 'admin']);
    }

    public function test_can_delete_permission()
    {
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            ActionType::VIEW
        );

        $this->assertDatabaseHas('permissions', ['slug' => 'users.view']);

        $this->rbacService->deletePermission($permission);

        $this->assertDatabaseMissing('permissions', ['slug' => 'users.view']);
    }

    public function test_clears_cache_when_permission_assigned_to_role()
    {
        Cache::flush();
        
        $role = $this->rbacService->createRole('Admin', 'admin');
        $permission = $this->rbacService->createPermission(
            'View Users',
            'users.view',
            'users',
            ActionType::VIEW
        );

        $this->rbacService->assignPermissionToRole($role, $permission);

        $this->assertFalse(Cache::has("rbac.role.{$role->id}.permissions"));
    }
}

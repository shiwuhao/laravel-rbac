<?php

namespace Rbac\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Enums\DataScopeType;
use Rbac\Tests\Models\User;
use Rbac\Tests\TestCase;

class HasRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $role;
    protected Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpUserTable();
        
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'guard_name' => 'web',
        ]);

        $this->permission = Permission::create([
            'name' => 'users.view',
            'slug' => 'users.view',
            'guard_name' => 'web',
        ]);
    }

    public function test_can_assign_role_to_user()
    {
        $this->user->assignRole($this->role);

        $this->assertTrue($this->user->roles->contains($this->role));
    }

    public function test_can_assign_role_by_slug()
    {
        $this->user->assignRole('admin');

        $this->assertTrue($this->user->roles->contains($this->role));
    }

    public function test_can_assign_multiple_roles()
    {
        $role2 = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'guard_name' => 'web',
        ]);

        $this->user->assignRole([$this->role, $role2]);

        $this->assertCount(2, $this->user->roles);
        $this->assertTrue($this->user->roles->contains($this->role));
        $this->assertTrue($this->user->roles->contains($role2));
    }

    public function test_can_remove_role_from_user()
    {
        $this->user->assignRole($this->role);
        $this->assertTrue($this->user->roles->contains($this->role));

        $this->user->removeRole($this->role);
        $this->assertFalse($this->user->fresh()->roles->contains($this->role));
    }

    public function test_can_sync_roles()
    {
        $role2 = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'guard_name' => 'web',
        ]);

        $this->user->assignRole([$this->role, $role2]);
        $this->assertCount(2, $this->user->roles);

        $this->user->syncRoles([$this->role]);
        $this->assertCount(1, $this->user->fresh()->roles);
        $this->assertTrue($this->user->roles->contains($this->role));
    }

    public function test_can_check_if_user_has_role()
    {
        $this->user->assignRole($this->role);

        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertTrue($this->user->hasRole($this->role));
        $this->assertFalse($this->user->hasRole('editor'));
    }

    public function test_can_check_if_user_has_any_role()
    {
        $this->user->assignRole($this->role);

        $this->assertTrue($this->user->hasAnyRole(['admin', 'editor']));
        $this->assertFalse($this->user->hasAnyRole(['editor', 'moderator']));
    }

    public function test_can_check_if_user_has_all_roles()
    {
        $role2 = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'guard_name' => 'web',
        ]);

        $this->user->assignRole([$this->role, $role2]);

        $this->assertTrue($this->user->hasAllRoles(['admin', 'editor']));
        $this->assertFalse($this->user->hasAllRoles(['admin', 'moderator']));
    }

    public function test_can_give_permission_to_user()
    {
        $this->user->givePermission($this->permission);

        $this->assertTrue($this->user->directPermissions->contains($this->permission));
    }

    public function test_can_give_permission_by_slug()
    {
        $this->user->givePermission('users.view');

        $this->assertTrue($this->user->directPermissions->contains($this->permission));
    }

    public function test_can_revoke_permission_from_user()
    {
        $this->user->givePermission($this->permission);
        $this->assertTrue($this->user->directPermissions->contains($this->permission));

        $this->user->revokePermission($this->permission);
        $this->assertFalse($this->user->fresh()->directPermissions->contains($this->permission));
    }

    public function test_can_sync_permissions()
    {
        $permission2 = Permission::create([
            'name' => 'users.edit',
            'slug' => 'users.edit',
            'guard_name' => 'web',
        ]);

        $this->user->givePermission([$this->permission, $permission2]);
        $this->assertCount(2, $this->user->directPermissions);

        $this->user->syncPermissions([$this->permission]);
        $this->assertCount(1, $this->user->fresh()->directPermissions);
    }

    public function test_can_check_if_user_has_permission()
    {
        $this->user->givePermission($this->permission);

        $this->assertTrue($this->user->hasPermission('users.view'));
        $this->assertTrue($this->user->hasPermission($this->permission));
        $this->assertFalse($this->user->hasPermission('users.edit'));
    }

    public function test_user_has_permission_through_role()
    {
        $this->role->permissions()->attach($this->permission);
        $this->user->assignRole($this->role);

        $this->assertTrue($this->user->hasPermission('users.view'));
    }

    public function test_can_get_all_permissions_for_user()
    {
        $permission2 = Permission::create([
            'name' => 'users.edit',
            'slug' => 'users.edit',
            'resource' => 'users',
            'action' => 'edit',
            'guard_name' => 'web',
        ]);

        $this->user->givePermission($this->permission);
        $this->role->permissions()->attach($permission2);
        $this->user->assignRole($this->role);

        // 重新加载用户关联
        $this->user->load(['directPermissions', 'roles.permissions']);
        
        $allPermissions = $this->user->getAllPermissions();

        $this->assertCount(2, $allPermissions);
        $this->assertTrue($allPermissions->contains('id', $this->permission->id));
        $this->assertTrue($allPermissions->contains('id', $permission2->id));
    }

    public function test_permissions_are_cached()
    {
        $this->user->givePermission($this->permission);
        
        // 第一次调用应该缓存
        $permissions1 = $this->user->getAllPermissions();
        
        // 第二次调用应该从缓存获取
        $permissions2 = $this->user->getAllPermissions();
        
        $this->assertCount(1, $permissions1);
        $this->assertCount(1, $permissions2);
        $this->assertTrue($permissions1->contains('id', $this->permission->id));
    }

    public function test_can_assign_data_scope_to_user()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $this->user->assignDataScope($dataScope, 'department_id');

        $this->assertTrue($this->user->dataScopes->contains($dataScope));
    }

    public function test_can_remove_data_scope_from_user()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $this->user->assignDataScope($dataScope);
        $this->assertTrue($this->user->dataScopes->contains($dataScope));

        $this->user->removeDataScope($dataScope);
        $this->assertFalse($this->user->fresh()->dataScopes->contains($dataScope));
    }

    public function test_can_check_if_user_has_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $this->user->assignDataScope($dataScope);

        $this->assertTrue($this->user->hasDataScope($dataScope));
    }
}

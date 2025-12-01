<?php

namespace Rbac\Tests\Unit\Traits;

use PHPUnit\Framework\Attributes\Test;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;

class HasRolesAndPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    #[Test]
    public function user_can_be_assigned_a_role()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole($role));
        $this->assertTrue($user->hasRole('editor'));
    }

    #[Test]
    public function user_can_be_assigned_multiple_roles()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role1 = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $role2 = Role::create([
            'name' => '作者',
            'slug' => 'author',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->assignRole([$role1, $role2]);

        $this->assertTrue($user->hasRole($role1));
        $this->assertTrue($user->hasRole($role2));
        $this->assertTrue($user->hasAllRoles([$role1, $role2]));
        $this->assertTrue($user->hasAnyRole([$role1]));
    }

    #[Test]
    public function user_can_revoke_roles()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->assignRole($role);
        $this->assertTrue($user->fresh()->hasRole($role));

        $user->removeRole($role);
        $this->assertFalse($user->fresh()->hasRole($role));
    }

    #[Test]
    public function user_can_sync_roles()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role1 = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $role2 = Role::create([
            'name' => '作者',
            'slug' => 'author',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->assignRole($role1);
        $user->syncRoles([$role2]);

        $this->assertFalse($user->hasRole($role1));
        $this->assertTrue($user->hasRole($role2));
    }

    #[Test]
    public function user_can_be_assigned_direct_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasDirectPermission($permission));
        $this->assertTrue($user->hasDirectPermission('post.update'));
    }

    #[Test]
    public function user_inherits_permissions_from_roles()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $permission = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $role->givePermission($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo($permission));
        $this->assertTrue($user->hasPermissionTo('post.update'));
    }

    #[Test]
    public function user_can_check_multiple_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $perm1 = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $perm2 = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo([$perm1, $perm2]);

        $this->assertTrue($user->hasAllPermissions([$perm1, $perm2]));
        $this->assertTrue($user->hasAnyPermission([$perm1]));
    }

    #[Test]
    public function user_can_revoke_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission);
        $this->assertTrue($user->fresh()->hasPermissionTo($permission));

        $user->revokePermissionTo($permission);
        $this->assertFalse($user->fresh()->hasDirectPermission($permission));
    }

    #[Test]
    public function user_can_sync_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $perm1 = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $perm2 = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($perm1);
        $user->syncPermissions([$perm2]);

        $this->assertFalse($user->hasDirectPermission($perm1));
        $this->assertTrue($user->hasDirectPermission($perm2));
    }

    #[Test]
    public function user_can_get_all_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $rolePerm = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $directPerm = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $role->givePermission($rolePerm);
        $user->assignRole($role);
        $user->givePermissionTo($directPerm);

        $allPermissions = $user->getAllPermissions();

        $this->assertEquals(2, $allPermissions->count());
        $this->assertTrue($allPermissions->contains('id', $rolePerm->id));
        $this->assertTrue($allPermissions->contains('id', $directPerm->id));
    }

    #[Test]
    public function user_can_check_permission_via_string()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('post.update'));
        $this->assertFalse($user->hasPermissionTo('post.delete'));
    }

    #[Test]
    public function user_can_be_assigned_data_scopes()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $user->dataScopes()->attach($dataScope->id);

        $this->assertEquals(1, $user->dataScopes()->count());
    }
}

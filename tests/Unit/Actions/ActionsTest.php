<?php

namespace Rbac\Tests\Unit\Actions;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Actions\Role\CreateRole;
use Rbac\Actions\Role\UpdateRole;
use Rbac\Actions\Role\DeleteRole;
use Rbac\Actions\Role\AssignPermissionsToRole;
use Rbac\Actions\Role\SyncPermissionsToRole;
use Rbac\Actions\Role\RevokePermissionFromRole;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Actions\Permission\UpdatePermission;
use Rbac\Actions\Permission\DeletePermission;
use Rbac\Actions\User\AssignRolesToUser;
use Rbac\Actions\User\SyncRolesToUser;
use Rbac\Actions\User\RevokeRoleFromUser;
use Rbac\Actions\User\AssignPermissionsToUser;
use Rbac\Actions\DataScope\CreateDataScope;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;
use Illuminate\Validation\ValidationException;

class ActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function it_can_create_role()
    {
        $action = new CreateRole();
        $result = $action->handle([
            'name' => '管理员',
            'slug' => 'admin',
            'description' => '系统管理员',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals('管理员', $result->name);
        $this->assertEquals('admin', $result->slug);
    }

    /** @test */
    public function it_validates_create_role_data()
    {
        $this->expectException(ValidationException::class);

        $action = new CreateRole();
        $action->handle([
            'slug' => 'admin', // missing name
        ]);
    }

    /** @test */
    public function it_can_update_role()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $action = new UpdateRole();
        $result = $action->handle([
            'name' => '高级编辑',
            'description' => '高级编辑权限',
        ], $role->id);

        $this->assertEquals('高级编辑', $result->name);
        $this->assertEquals('高级编辑权限', $result->description);
    }

    /** @test */
    public function it_can_delete_role()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $action = new DeleteRole();
        $action->handle([], $role->id);

        $this->assertDatabaseMissing(config('rbac.tables.roles'), [
            'id' => $role->id,
        ]);
    }

    /** @test */
    public function it_can_assign_permissions_to_role()
    {
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

        $action = new AssignPermissionsToRole();
        $result = $action->handle([
            'permission_ids' => [$permission->id],
        ], $role->id);

        $this->assertTrue($result->hasPermission($permission));
    }

    /** @test */
    public function it_can_sync_permissions_to_role()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
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

        $role->givePermission($perm1);

        $action = new SyncPermissionsToRole();
        $result = $action->handle([
            'permission_ids' => [$perm2->id],
        ], $role->id);

        $this->assertFalse($result->hasPermission($perm1));
        $this->assertTrue($result->hasPermission($perm2));
    }

    /** @test */
    public function it_can_revoke_permission_from_role()
    {
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

        $action = new RevokePermissionFromRole();
        $result = $action->handle([
            'permission_id' => $permission->id,
        ], $role->id);

        $this->assertFalse($result->hasPermission($permission));
    }

    /** @test */
    public function it_can_create_permission()
    {
        $action = new CreatePermission();
        $result = $action->handle([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'description' => '查看文章权限',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertInstanceOf(Permission::class, $result);
        $this->assertEquals('查看文章', $result->name);
        $this->assertEquals('post.view', $result->slug);
    }

    /** @test */
    public function it_can_update_permission()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $action = new UpdatePermission();
        $result = $action->handle([
            'description' => '查看所有文章',
        ], $permission->id);

        $this->assertEquals('查看所有文章', $result->description);
    }

    /** @test */
    public function it_can_delete_permission()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $action = new DeletePermission();
        $action->handle([], $permission->id);

        $this->assertSoftDeleted(config('rbac.tables.permissions'), [
            'id' => $permission->id,
        ]);
    }

    /** @test */
    public function it_can_assign_roles_to_user()
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

        $action = new AssignRolesToUser();
        $result = $action->handle([
            'role_ids' => [$role->id],
        ], $user->id);

        $this->assertTrue($result->hasRole($role));
    }

    /** @test */
    public function it_can_sync_roles_to_user()
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

        $action = new SyncRolesToUser();
        $result = $action->handle([
            'role_ids' => [$role2->id],
        ], $user->id);

        $this->assertFalse($result->hasRole($role1));
        $this->assertTrue($result->hasRole($role2));
    }

    /** @test */
    public function it_can_revoke_role_from_user()
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

        $action = new RevokeRoleFromUser();
        $result = $action->handle([
            'role_ids' => [$role->id],
        ], $user->id);

        $this->assertFalse($result->hasRole($role));
    }

    /** @test */
    public function it_can_assign_permissions_to_user()
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

        $action = new AssignPermissionsToUser();
        $result = $action->handle([
            'permission_ids' => [$permission->id],
        ], $user->id);

        $this->assertTrue($result->hasDirectPermission($permission));
    }

    /** @test */
    public function it_can_create_data_scope()
    {
        $action = new CreateDataScope();
        $result = $action->handle([
            'name' => '个人数据',
            'type' => 'personal',
            'description' => '只能查看个人数据',
        ]);

        $this->assertInstanceOf(DataScope::class, $result);
        $this->assertEquals('个人数据', $result->name);
        $this->assertEquals(DataScopeType::PERSONAL, $result->type);
    }
}

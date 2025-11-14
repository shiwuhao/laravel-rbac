<?php

namespace Rbac\Tests\Unit\Models;

use Rbac\Tests\TestCase;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Tests\Models\User;
use Rbac\Enums\GuardType;

class RoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::create([
            'name' => '管理员',
            'slug' => 'admin',
            'description' => '系统管理员',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertDatabaseHas(config('rbac.tables.roles'), [
            'name' => '管理员',
            'slug' => 'admin',
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

        $role->givePermission($permission);

        $this->assertTrue($role->hasPermission($permission));
        $this->assertTrue($role->hasPermission('post.update'));
    }

    /** @test */
    public function it_can_check_multiple_permissions()
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

        $role->givePermission([$perm1, $perm2]);

        $this->assertTrue($role->hasAllPermissions([$perm1, $perm2]));
        $this->assertTrue($role->hasAnyPermission([$perm1]));
    }

    /** @test */
    public function it_can_revoke_permissions()
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
        $this->assertTrue($role->hasPermission($permission));

        $role->revokePermission($permission);
        $this->assertFalse($role->hasPermission($permission));
    }

    /** @test */
    public function it_can_sync_permissions()
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
        $role->syncPermissions([$perm2]);

        $this->assertFalse($role->hasPermission($perm1));
        $this->assertTrue($role->hasPermission($perm2));
    }

    /** @test */
    public function it_has_relationships()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $role->permissions());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $role->users());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $role->dataScopes());
    }

    /** @test */
    public function it_can_use_scopes()
    {
        Role::create([
            'name' => '管理员',
            'slug' => 'admin',
            'guard_name' => GuardType::WEB->value,
        ]);

        Role::create([
            'name' => 'API管理员',
            'slug' => 'api-admin',
            'guard_name' => GuardType::API->value,
        ]);

        $this->assertEquals(1, Role::bySlug('admin')->count());
        $this->assertEquals(1, Role::byName('管理员')->count());
        $this->assertEquals(1, Role::byGuard(GuardType::WEB)->count());
    }

    /** @test */
    public function it_supports_soft_deletes()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $role->delete();

        $this->assertSoftDeleted(config('rbac.tables.roles'), [
            'id' => $role->id,
        ]);
    }
}

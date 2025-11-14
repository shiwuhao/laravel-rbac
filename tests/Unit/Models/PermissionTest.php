<?php

namespace Rbac\Tests\Unit\Models;

use Rbac\Tests\TestCase;
use Rbac\Models\Permission;
use Rbac\Models\Role;
use Rbac\Enums\GuardType;

class PermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function it_can_create_a_permission()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'description' => '查看文章权限',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertDatabaseHas(config('rbac.tables.permissions'), [
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
        ]);
    }

    /** @test */
    public function it_can_create_instance_permission()
    {
        $permission = Permission::create([
            'name' => '查看文章#1',
            'slug' => 'post.view.1',
            'resource' => 'post',
            'action' => 'view',
            'resource_type' => 'App\Models\Post',
            'resource_id' => 1,
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertTrue($permission->isInstancePermission());
        $this->assertFalse($permission->isGeneralPermission());
    }

    /** @test */
    public function it_can_generate_slug()
    {
        $slug = Permission::generateSlug('post', 'view');
        $this->assertEquals('post.view', $slug);

        $instanceSlug = Permission::generateSlug('post', 'view', 1);
        $this->assertEquals('post.view.1', $instanceSlug);
    }

    /** @test */
    public function it_can_generate_name()
    {
        $name = Permission::generateName('文章', 'view');
        $this->assertEquals('查看文章', $name);

        $instanceName = Permission::generateName('文章', 'view', 1);
        $this->assertEquals('查看文章(#1)', $instanceName);
    }

    /** @test */
    public function it_can_check_if_write_operation()
    {
        $viewPerm = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $createPerm = Permission::create([
            'name' => '创建文章',
            'slug' => 'post.create',
            'resource' => 'post',
            'action' => 'create',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertFalse($viewPerm->isWriteOperation());
        $this->assertTrue($createPerm->isWriteOperation());
    }

    /** @test */
    public function it_can_use_scopes()
    {
        Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        Permission::create([
            'name' => '创建文章',
            'slug' => 'post.create',
            'resource' => 'post',
            'action' => 'create',
            'guard_name' => GuardType::WEB->value,
        ]);

        Permission::create([
            'name' => '查看用户',
            'slug' => 'user.view',
            'resource' => 'user',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertEquals(2, Permission::byResource('post')->count());
        $this->assertEquals(1, Permission::byAction('create')->count());
        $this->assertEquals(1, Permission::byResourceAction('post', 'view')->count());
        $this->assertEquals(3, Permission::byGuard(GuardType::WEB)->count());
        $this->assertEquals(1, Permission::writeOperations()->count());
        $this->assertEquals(2, Permission::readOperations()->count());
    }

    /** @test */
    public function it_has_relationships()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $permission->roles());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $permission->users());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $permission->dataScopes());
    }

    /** @test */
    public function it_can_get_full_description()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertEquals('查看 - post', $permission->full_description);

        $permWithDesc = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'description' => '编辑文章的权限',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertEquals('编辑文章的权限', $permWithDesc->full_description);
    }

    /** @test */
    public function it_supports_soft_deletes()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $permission->delete();

        $this->assertSoftDeleted(config('rbac.tables.permissions'), [
            'id' => $permission->id,
        ]);
    }

    /** @test */
    public function it_can_store_metadata()
    {
        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
            'metadata' => [
                'route' => 'posts.index',
                'method' => 'GET',
            ],
        ]);

        $this->assertEquals('posts.index', $permission->metadata['route']);
        $this->assertEquals('GET', $permission->metadata['method']);
    }
}

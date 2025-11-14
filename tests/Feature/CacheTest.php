<?php

namespace Rbac\Tests\Feature;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Enums\GuardType;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function it_caches_user_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission);

        // 第一次调用，应该缓存
        $permissions1 = $user->getAllPermissions();
        
        // 第二次调用，应该从缓存获取
        $permissions2 = $user->getAllPermissions();

        $this->assertEquals($permissions1->count(), $permissions2->count());
    }

    /** @test */
    public function it_clears_cache_when_permission_changes()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission);
        $user = $user->fresh(['directPermissions']);
        $count1 = $user->getAllPermissions()->count();

        $permission2 = Permission::create([
            'name' => '编辑文章',
            'slug' => 'post.update',
            'resource' => 'post',
            'action' => 'update',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission2);
        $user = $user->fresh(['directPermissions']);
        $count2 = $user->getAllPermissions()->count();

        $this->assertEquals($count1 + 1, $count2);
    }

    /** @test */
    public function it_clears_cache_when_role_changes()
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
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $role->givePermission($permission);

        $user = $user->fresh(['roles']);
        $count1 = $user->getAllPermissions()->count();
        $this->assertEquals(0, $count1);

        $user->assignRole($role);
        $user = $user->fresh(['roles']);
        $count2 = $user->getAllPermissions()->count();

        $this->assertEquals(1, $count2);
    }

    /** @test */
    public function it_can_manually_forget_cache()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $permission = Permission::create([
            'name' => '查看文章',
            'slug' => 'post.view',
            'resource' => 'post',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo($permission);
        $user->getAllPermissions(); // 缓存权限

        // 手动清除缓存
        $user->forgetCachedPermissions();

        // 重新加载应该获取最新数据
        $permissions = $user->getAllPermissions();
        $this->assertEquals(1, $permissions->count());
    }
}

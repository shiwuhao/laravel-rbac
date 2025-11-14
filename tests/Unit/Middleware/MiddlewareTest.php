<?php

namespace Rbac\Tests\Unit\Middleware;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Middleware\PermissionMiddleware;
use Rbac\Middleware\RoleMiddleware;
use Rbac\Enums\GuardType;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function permission_middleware_allows_user_with_permission()
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

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new PermissionMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'post.view');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function permission_middleware_denies_user_without_permission()
    {
        $this->expectException(AuthorizationException::class);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new PermissionMiddleware();
        $middleware->handle($request, fn($req) => response('OK'), 'post.view');
    }

    /** @test */
    public function permission_middleware_requires_authentication()
    {
        $this->expectException(AuthenticationException::class);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => null);

        $middleware = new PermissionMiddleware();
        $middleware->handle($request, fn($req) => response('OK'), 'post.view');
    }

    /** @test */
    public function permission_middleware_supports_or_logic()
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

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new PermissionMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'post.view|post.update');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function permission_middleware_supports_and_logic()
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

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new PermissionMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'post.view&post.update');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function role_middleware_allows_user_with_role()
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

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new RoleMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'editor');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function role_middleware_denies_user_without_role()
    {
        $this->expectException(AuthorizationException::class);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new RoleMiddleware();
        $middleware->handle($request, fn($req) => response('OK'), 'editor');
    }

    /** @test */
    public function role_middleware_supports_or_logic()
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

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new RoleMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'editor|admin');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function role_middleware_supports_and_logic()
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

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new RoleMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'editor&author');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function user_with_role_permission_can_access()
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
        $user->assignRole($role);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new PermissionMiddleware();
        $response = $middleware->handle($request, fn($req) => response('OK'), 'post.view');

        $this->assertEquals('OK', $response->getContent());
    }
}

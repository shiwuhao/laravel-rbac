<?php

namespace Rbac\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Middleware\PermissionMiddleware;
use Rbac\Middleware\RoleMiddleware;
use Rbac\Tests\Models\User;
use Rbac\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;
    protected Role $editorRole;
    protected Permission $viewPermission;
    protected Permission $editPermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpUserTable();
        
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'guard_name' => 'web',
        ]);

        $this->editorRole = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'guard_name' => 'web',
        ]);

        $this->viewPermission = Permission::create([
            'name' => 'users.view',
            'slug' => 'users.view',
            'resource' => 'users',
            'action' => 'view',
            'guard_name' => 'web',
        ]);

        $this->editPermission = Permission::create([
            'name' => 'users.edit',
            'slug' => 'users.edit',
            'resource' => 'users',
            'action' => 'edit',
            'guard_name' => 'web',
        ]);
    }

    public function test_permission_middleware_throws_exception_for_unauthenticated_user()
    {
        $this->expectException(AuthenticationException::class);

        $request = Request::create('/test', 'GET');
        $middleware = new PermissionMiddleware();

        $middleware->handle($request, function () {
            return response('OK');
        }, 'users.view');
    }

    public function test_permission_middleware_allows_user_with_permission()
    {
        $this->user->givePermission($this->viewPermission);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new PermissionMiddleware();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'users.view');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_permission_middleware_denies_user_without_permission()
    {
        $this->expectException(AuthorizationException::class);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new PermissionMiddleware();

        $middleware->handle($request, function () {
            return response('OK');
        }, 'users.edit');
    }

    public function test_permission_middleware_supports_or_logic()
    {
        $this->user->givePermission($this->viewPermission);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new PermissionMiddleware();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'users.view|users.edit');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_permission_middleware_supports_and_logic()
    {
        $this->user->givePermission([$this->viewPermission, $this->editPermission]);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new PermissionMiddleware();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'users.view&users.edit');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_permission_middleware_and_logic_fails_without_all_permissions()
    {
        $this->expectException(AuthorizationException::class);

        $this->user->givePermission($this->viewPermission);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new PermissionMiddleware();

        $middleware->handle($request, function () {
            return response('OK');
        }, 'users.view&users.edit');
    }

    public function test_role_middleware_throws_exception_for_unauthenticated_user()
    {
        $this->expectException(AuthenticationException::class);

        $request = Request::create('/test', 'GET');
        $middleware = new RoleMiddleware();

        $middleware->handle($request, function () {
            return response('OK');
        }, 'admin');
    }

    public function test_role_middleware_allows_user_with_role()
    {
        $this->user->assignRole($this->adminRole);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new RoleMiddleware();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'admin');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_role_middleware_denies_user_without_role()
    {
        $this->expectException(AuthorizationException::class);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new RoleMiddleware();

        $middleware->handle($request, function () {
            return response('OK');
        }, 'admin');
    }

    public function test_role_middleware_supports_or_logic()
    {
        $this->user->assignRole($this->adminRole);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new RoleMiddleware();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'admin|editor');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_role_middleware_supports_and_logic()
    {
        $this->user->assignRole([$this->adminRole, $this->editorRole]);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new RoleMiddleware();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'admin&editor');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_role_middleware_and_logic_fails_without_all_roles()
    {
        $this->expectException(AuthorizationException::class);

        $this->user->assignRole($this->adminRole);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $middleware = new RoleMiddleware();

        $middleware->handle($request, function () {
            return response('OK');
        }, 'admin&editor');
    }

    public function test_middleware_allows_super_admin()
    {
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'guard_name' => 'web',
            'is_super_admin' => true,
        ]);

        $this->user->assignRole($superAdminRole);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        $permissionMiddleware = new PermissionMiddleware();
        $roleMiddleware = new RoleMiddleware();

        $response1 = $permissionMiddleware->handle($request, function () {
            return response('OK');
        }, 'any.permission');

        $response2 = $roleMiddleware->handle($request, function () {
            return response('OK');
        }, 'any-role');

        $this->assertEquals('OK', $response1->getContent());
        $this->assertEquals('OK', $response2->getContent());
    }
}

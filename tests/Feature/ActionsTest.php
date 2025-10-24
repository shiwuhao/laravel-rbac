<?php

namespace Rbac\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Rbac\Actions\Role\CreateRole;
use Rbac\Actions\Role\UpdateRole;
use Rbac\Actions\Role\DeleteRole;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Actions\DataScope\CreateDataScope;
use Rbac\Actions\ActionContext;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Enums\DataScopeType;
use Rbac\Tests\TestCase;

class ActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_role_action_creates_role()
    {
        $action = new CreateRole();
        $role = $action::handle([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Admin role',
            'guard_name' => 'web',
        ]);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Administrator', $role->name);
        $this->assertEquals('admin', $role->slug);
        $this->assertEquals('Admin role', $role->description);
        
        $this->assertDatabaseHas('roles', [
            'slug' => 'admin',
            'name' => 'Administrator',
        ]);
    }

    public function test_create_role_action_validates_input()
    {
        $this->expectException(ValidationException::class);

        CreateRole::handle([
            'name' => '',
        ]);
    }

    public function test_create_role_action_prevents_duplicate_slug()
    {
        $this->expectException(ValidationException::class);

        Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'guard_name' => 'web',
        ]);

        CreateRole::handle([
            'name' => 'Administrator',
            'slug' => 'admin',
        ]);
    }

    public function test_update_role_action_updates_role()
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'guard_name' => 'web',
        ]);

        $updatedRole = UpdateRole::handle([
            'name' => 'Super Admin',
            'description' => 'Updated description',
        ], $role->id);

        $this->assertEquals('Super Admin', $updatedRole->name);
        $this->assertEquals('Updated description', $updatedRole->description);
        
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Super Admin',
        ]);
    }

    public function test_delete_role_action_deletes_role()
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'guard_name' => 'web',
        ]);

        $result = DeleteRole::handle([], $role->id);

        $this->assertIsArray($result);
        $this->assertTrue($result['deleted']);
        
        // 使用withTrashed检查是否真的被删除
        $this->assertNull(Role::withTrashed()->find($role->id));
    }

    public function test_create_permission_action_creates_permission()
    {
        $permission = CreatePermission::handle([
            'name' => 'View Users',
            'slug' => 'users.view',
            'resource' => 'users',
            'action' => 'view',
            'description' => 'Can view users',
            'guard_name' => 'web',
        ]);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('View Users', $permission->name);
        $this->assertEquals('users.view', $permission->slug);
        $this->assertEquals('users', $permission->resource);
        
        $this->assertDatabaseHas('permissions', [
            'slug' => 'users.view',
            'resource' => 'users',
        ]);
    }

    public function test_create_permission_action_validates_input()
    {
        $this->expectException(ValidationException::class);

        CreatePermission::handle([
            'name' => 'View Users',
        ]);
    }

    public function test_create_data_scope_action_creates_data_scope()
    {
        $dataScope = CreateDataScope::handle([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT->value,
            'config' => ['department_ids' => [1, 2, 3]],
            'description' => 'Department level access',
        ]);

        $this->assertInstanceOf(DataScope::class, $dataScope);
        $this->assertEquals('Department Scope', $dataScope->name);
        $this->assertEquals(DataScopeType::DEPARTMENT, $dataScope->type);
        $this->assertEquals(['department_ids' => [1, 2, 3]], $dataScope->config);
        
        $this->assertDatabaseHas('data_scopes', [
            'name' => 'Department Scope',
        ]);
    }

    public function test_action_returns_json_response_on_success()
    {
        $action = new CreateRole();
        $response = $action([
            'name' => 'Test Role',
            'slug' => 'test-role',
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $data['code']);
        $this->assertArrayHasKey('data', $data);
    }

    public function test_action_returns_json_response_on_validation_error()
    {
        $action = new CreateRole();
        $response = $action(['name' => '']);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('message', $data);
    }

    public function test_action_context_can_retrieve_data()
    {
        $context = new ActionContext([
            'name' => 'Test',
            'slug' => 'test',
        ]);

        $this->assertEquals('Test', $context->data('name'));
        $this->assertEquals('test', $context->data('slug'));
        $this->assertEquals('default', $context->data('missing', 'default'));
    }

    public function test_action_context_can_check_data_exists()
    {
        $context = new ActionContext([
            'name' => 'Test',
        ]);

        $this->assertTrue($context->has('name'));
        $this->assertFalse($context->has('slug'));
    }
}

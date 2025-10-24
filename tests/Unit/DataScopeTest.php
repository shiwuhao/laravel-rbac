<?php

namespace Rbac\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Rbac\Models\DataScope;
use Rbac\Models\Permission;
use Rbac\Enums\DataScopeType;
use Rbac\Tests\TestCase;

class DataScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
            'description' => 'Department level data access',
        ]);

        $this->assertDatabaseHas('data_scopes', [
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT->value,
            'description' => 'Department level data access',
        ]);

        $this->assertInstanceOf(DataScope::class, $dataScope);
    }

    public function test_can_create_data_scope_with_config()
    {
        $config = ['department_ids' => [1, 2, 3]];
        
        $dataScope = DataScope::create([
            'name' => 'Custom Scope',
            'type' => DataScopeType::CUSTOM,
            'config' => $config,
        ]);

        $this->assertEquals($config, $dataScope->config);
    }

    public function test_data_scope_type_is_enum()
    {
        $dataScope = DataScope::create([
            'name' => 'All Data Scope',
            'type' => DataScopeType::ALL,
        ]);

        $this->assertInstanceOf(DataScopeType::class, $dataScope->type);
        $this->assertEquals(DataScopeType::ALL, $dataScope->type);
    }

    public function test_can_attach_permissions_to_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $permission = Permission::create([
            'name' => 'users.view',
            'slug' => 'users.view',
            'guard_name' => 'web',
        ]);

        $dataScope->permissions()->attach($permission, ['constraint' => 'department_id']);

        $this->assertTrue($dataScope->permissions->contains($permission));
        $this->assertEquals('department_id', $dataScope->permissions->first()->pivot->constraint);
    }

    public function test_can_detach_permissions_from_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $permission = Permission::create([
            'name' => 'users.view',
            'slug' => 'users.view',
            'guard_name' => 'web',
        ]);

        $dataScope->permissions()->attach($permission);
        $this->assertTrue($dataScope->permissions->contains($permission));

        $dataScope->permissions()->detach($permission);
        $this->assertFalse($dataScope->fresh()->permissions->contains($permission));
    }

    public function test_can_get_permissions_for_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => 'Department Scope',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $permission1 = Permission::create([
            'name' => 'users.view',
            'slug' => 'users.view',
            'guard_name' => 'web',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'slug' => 'users.edit',
            'guard_name' => 'web',
        ]);

        $dataScope->permissions()->attach([$permission1->id, $permission2->id]);

        $this->assertCount(2, $dataScope->permissions);
        $this->assertTrue($dataScope->permissions->contains($permission1));
        $this->assertTrue($dataScope->permissions->contains($permission2));
    }

    public function test_data_scope_factory_works()
    {
        $dataScope = DataScope::factory()->create([
            'name' => 'Test Scope',
        ]);

        $this->assertInstanceOf(DataScope::class, $dataScope);
        $this->assertEquals('Test Scope', $dataScope->name);
        $this->assertInstanceOf(DataScopeType::class, $dataScope->type);
    }
}

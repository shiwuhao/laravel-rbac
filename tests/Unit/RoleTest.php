<?php

namespace Rbac\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_role()
    {
        $role = Role::create([
            'name' => 'admin',
            'slug' => 'admin',
            'guard_name' => 'web',
            'description' => 'Administrator role',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'slug' => 'admin',
            'guard_name' => 'web',
            'description' => 'Administrator role',
        ]);

        $this->assertInstanceOf(Role::class, $role);
    }

    public function test_it_can_assign_permissions_to_a_role()
    {
        $role = Role::create([
            'name' => 'admin',
            'slug' => 'admin',
            'guard_name' => 'web',
        ]);

        $permission = Permission::create([
            'name' => 'edit-users',
            'slug' => 'edit-users',
            'resource' => 'users',
            'action' => 'edit',
            'guard_name' => 'web',
        ]);

        $role->permissions()->attach($permission);

        $this->assertTrue($role->permissions->contains($permission));
    }
}
<?php

namespace Rbac\Tests\Unit;

use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Tests\TestCase;

class RoleTest extends TestCase
{
    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'description' => 'Administrator role',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'guard_name' => 'web',
            'description' => 'Administrator role',
        ]);

        $this->assertInstanceOf(Role::class, $role);
    }

    /** @test */
    public function it_can_assign_permissions_to_a_role()
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $permission = Permission::create([
            'name' => 'edit-users',
            'guard_name' => 'web',
        ]);

        $role->permissions()->attach($permission);

        $this->assertTrue($role->permissions->contains($permission));
    }
}
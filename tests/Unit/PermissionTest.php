<?php

namespace Rbac\Tests\Unit;

use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Tests\TestCase;

class PermissionTest extends TestCase
{
    /** @test */
    public function it_can_create_a_permission()
    {
        $permission = Permission::create([
            'name' => 'edit-users',
            'guard_name' => 'web',
            'description' => 'Allow editing users',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'edit-users',
            'guard_name' => 'web',
            'description' => 'Allow editing users',
        ]);

        $this->assertInstanceOf(Permission::class, $permission);
    }

    /** @test */
    public function it_can_assign_roles_to_a_permission()
    {
        $permission = Permission::create([
            'name' => 'edit-users',
            'guard_name' => 'web',
        ]);

        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $permission->roles()->attach($role);

        $this->assertTrue($permission->roles->contains($role));
    }
}
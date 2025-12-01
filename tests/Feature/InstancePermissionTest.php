<?php

namespace Rbac\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Actions\Role\AssignInstancePermissionToRole;
use Rbac\Actions\Role\RevokeInstancePermissionFromRole;
use Rbac\Actions\User\AssignInstancePermissionToUser;
use Rbac\Actions\User\RevokeInstancePermissionFromUser;
use Rbac\Enums\GuardType;

class InstancePermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    #[Test]
    public function it_can_create_instance_permission()
    {
        $permission = Permission::create([
            'name' => '查看报表#1',
            'slug' => 'report.view.1',
            'resource' => 'report',
            'action' => 'view',
            'resource_type' => 'App\\Models\\Report',
            'resource_id' => 1,
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertTrue($permission->isInstancePermission());
        $this->assertFalse($permission->isGeneralPermission());
        $this->assertEquals('App\\Models\\Report', $permission->resource_type);
        $this->assertEquals(1, $permission->resource_id);
    }

    #[Test]
    public function it_can_distinguish_general_permission()
    {
        $permission = Permission::create([
            'name' => '查看报表',
            'slug' => 'report.view',
            'resource' => 'report',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertFalse($permission->isInstancePermission());
        $this->assertTrue($permission->isGeneralPermission());
    }

    #[Test]
    public function user_can_have_both_general_and_instance_permissions()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $generalPerm = Permission::create([
            'name' => '查看报表',
            'slug' => 'report.view',
            'resource' => 'report',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        $instancePerm = Permission::create([
            'name' => '查看报表#1',
            'slug' => 'report.view.1',
            'resource' => 'report',
            'action' => 'view',
            'resource_type' => 'App\\Models\\Report',
            'resource_id' => 1,
            'guard_name' => GuardType::WEB->value,
        ]);

        $user->givePermissionTo([$generalPerm, $instancePerm]);

        $this->assertTrue($user->hasPermissionTo($generalPerm));
        $this->assertTrue($user->hasPermissionTo($instancePerm));
        $this->assertEquals(2, $user->getAllPermissions()->count());
    }
}

<?php

namespace Rbac\Tests\Feature;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;

class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function data_scope_workflow()
    {
        // 1. 创建数据范围
        $allDataScope = DataScope::create([
            'name' => '全部数据',
            'type' => DataScopeType::ALL,
        ]);

        $personalDataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $departmentDataScope = DataScope::create([
            'name' => '部门数据',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        // 2. 创建角色
        $adminRole = Role::create([
            'name' => '管理员',
            'slug' => 'admin',
            'guard_name' => GuardType::WEB->value,
        ]);

        $managerRole = Role::create([
            'name' => '经理',
            'slug' => 'manager',
            'guard_name' => GuardType::WEB->value,
        ]);

        $staffRole = Role::create([
            'name' => '员工',
            'slug' => 'staff',
            'guard_name' => GuardType::WEB->value,
        ]);

        // 3. 分配数据范围给角色
        $adminRole->dataScopes()->attach($allDataScope->id);
        $managerRole->dataScopes()->attach($departmentDataScope->id);
        $staffRole->dataScopes()->attach($personalDataScope->id);

        // 4. 验证数据范围
        $this->assertEquals(1, $adminRole->dataScopes()->count());
        $this->assertEquals(1, $managerRole->dataScopes()->count());
        $this->assertEquals(1, $staffRole->dataScopes()->count());

        // 5. 创建用户并分配角色
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
        ]);

        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $admin->assignRole($adminRole);
        $manager->assignRole($managerRole);
        $staff->assignRole($staffRole);

        // 6. 验证用户可以访问对应的数据范围
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($manager->hasRole('manager'));
        $this->assertTrue($staff->hasRole('staff'));
    }

    /** @test */
    public function instance_permission_workflow()
    {
        // 1. 创建通用权限
        $generalViewPerm = Permission::create([
            'name' => '查看报表',
            'slug' => 'report.view',
            'resource' => 'report',
            'action' => 'view',
            'guard_name' => GuardType::WEB->value,
        ]);

        // 2. 创建实例权限
        $instancePerm1 = Permission::create([
            'name' => '查看报表#1',
            'slug' => 'report.view.1',
            'resource' => 'report',
            'action' => 'view',
            'resource_type' => 'App\\Models\\Report',
            'resource_id' => 1,
            'guard_name' => GuardType::WEB->value,
        ]);

        $instancePerm2 = Permission::create([
            'name' => '查看报表#2',
            'slug' => 'report.view.2',
            'resource' => 'report',
            'action' => 'view',
            'resource_type' => 'App\\Models\\Report',
            'resource_id' => 2,
            'guard_name' => GuardType::WEB->value,
        ]);

        // 3. 创建用户
        $userWithGeneral = User::create([
            'name' => 'General User',
            'email' => 'general@example.com',
            'password' => 'password',
        ]);

        $userWithInstance = User::create([
            'name' => 'Instance User',
            'email' => 'instance@example.com',
            'password' => 'password',
        ]);

        // 4. 分配权限
        $userWithGeneral->givePermissionTo($generalViewPerm);
        $userWithInstance->givePermissionTo([$instancePerm1, $instancePerm2]);

        // 5. 验证权限
        $this->assertTrue($userWithGeneral->hasPermissionTo('report.view'));
        $this->assertTrue($userWithInstance->hasPermissionTo('report.view.1'));
        $this->assertTrue($userWithInstance->hasPermissionTo('report.view.2'));
        $this->assertFalse($userWithInstance->hasPermissionTo('report.view'));

        // 6. 验证实例权限特性
        $this->assertTrue($instancePerm1->isInstancePermission());
        $this->assertFalse($generalViewPerm->isInstancePermission());
    }
}

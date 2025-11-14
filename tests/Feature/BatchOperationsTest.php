<?php

namespace Rbac\Tests\Feature;

use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Actions\Permission\BatchCreatePermissions;
use Rbac\Actions\Permission\CreateInstancePermission;
use Rbac\Actions\Role\BatchDeleteRoles;
use Rbac\Actions\Permission\BatchDeletePermissions;
use Rbac\Actions\DataScope\BatchDeleteDataScopes;
use Rbac\Actions\User\GetUserPermissions;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;

class BatchOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function it_can_batch_create_permissions()
    {
        $action = new BatchCreatePermissions();
        $result = $action->handle([
            'resource' => '文章',
            'actions' => ['view', 'create', 'update', 'delete'],
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertCount(4, $result);
        $this->assertEquals('文章.view', $result->first()->slug);
        $this->assertEquals('查看文章', $result->first()->name);
    }

    /** @test */
    public function it_can_create_instance_permission()
    {
        $action = new CreateInstancePermission();
        $result = $action->handle([
            'name' => '查看报表#1',
            'slug' => 'report.view.1',
            'resource' => 'report',
            'action' => 'view',
            'resource_type' => 'App\\Models\\Report',
            'resource_id' => 1,
            'guard_name' => GuardType::WEB->value,
        ]);

        $this->assertTrue($result->isInstancePermission());
        $this->assertEquals('App\\Models\\Report', $result->resource_type);
        $this->assertEquals(1, $result->resource_id);
    }

    /** @test */
    public function it_can_batch_delete_roles()
    {
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

        $action = new BatchDeleteRoles();
        $result = $action->handle([
            'role_ids' => [$role1->id, $role2->id],
            'force' => true,
        ]);

        $this->assertEquals(2, $result['deleted']);
        $this->assertDatabaseMissing(config('rbac.tables.roles'), ['id' => $role1->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing(config('rbac.tables.roles'), ['id' => $role2->id, 'deleted_at' => null]);
    }

    /** @test */
    public function it_can_batch_delete_permissions()
    {
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

        $action = new BatchDeletePermissions();
        $result = $action->handle([
            'permission_ids' => [$perm1->id, $perm2->id],
            'force' => true,
        ]);

        $this->assertEquals(2, $result['deleted']);
        $this->assertSoftDeleted(config('rbac.tables.permissions'), ['id' => $perm1->id]);
        $this->assertSoftDeleted(config('rbac.tables.permissions'), ['id' => $perm2->id]);
    }

    /** @test */
    public function it_can_batch_delete_data_scopes()
    {
        $scope1 = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $scope2 = DataScope::create([
            'name' => '部门数据',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $action = new BatchDeleteDataScopes();
        $result = $action->handle([
            'data_scope_ids' => [$scope1->id, $scope2->id],
        ]);

        $this->assertEquals(2, $result['deleted']);
        $this->assertDatabaseMissing(config('rbac.tables.data_scopes'), ['id' => $scope1->id]);
        $this->assertDatabaseMissing(config('rbac.tables.data_scopes'), ['id' => $scope2->id]);
    }

}

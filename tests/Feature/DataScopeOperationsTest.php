<?php

namespace Rbac\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Rbac\Actions\Role\RevokeDataScopesFromRole;
use Rbac\Actions\User\RevokeDataScopesFromUser;
use Rbac\Tests\TestCase;
use Rbac\Tests\Models\User;
use Rbac\Models\Role;
use Rbac\Models\DataScope;
use Rbac\Actions\Role\AssignDataScopesToRole;
use Rbac\Actions\Role\SyncDataScopesToRole;
use Rbac\Actions\User\AssignDataScopesToUser;
use Rbac\Actions\User\SyncDataScopesToUser;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;

class DataScopeOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    #[Test]
    public function it_can_assign_data_scopes_to_role()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $action = new AssignDataScopesToRole();
        $result = $action->handle([
            'data_scope_ids' => [$dataScope->id],
        ], $role->id);

        $this->assertEquals(1, $result->dataScopes()->count());
    }

    #[Test]
    public function it_can_sync_data_scopes_to_role()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $scope1 = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $scope2 = DataScope::create([
            'name' => '部门数据',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $role->dataScopes()->attach($scope1->id);

        $action = new SyncDataScopesToRole();
        $result = $action->handle([
            'data_scope_ids' => [$scope2->id],
        ], $role->id);

        $this->assertEquals(1, $result->dataScopes()->count());
        $this->assertEquals($scope2->id, $result->dataScopes()->first()->id);
    }

    #[Test]
    public function it_can_revoke_data_scope_from_role()
    {
        $role = Role::create([
            'name' => '编辑',
            'slug' => 'editor',
            'guard_name' => GuardType::WEB->value,
        ]);

        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $role->dataScopes()->attach($dataScope->id);

        $action = new RevokeDataScopesFromRole();
        $result = $action->handle([
            'data_scope_id' => $dataScope->id,
        ], $role->id);

        $this->assertEquals(0, $result->dataScopes()->count());
    }

    #[Test]
    public function it_can_assign_data_scopes_to_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $action = new AssignDataScopesToUser();
        $result = $action->handle([
            'data_scope_ids' => [$dataScope->id],
        ], $user->id);

        $this->assertEquals(1, $result->dataScopes()->count());
    }

    #[Test]
    public function it_can_sync_data_scopes_to_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $scope1 = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $scope2 = DataScope::create([
            'name' => '部门数据',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $user->directDataScopes()->attach($scope1->id);

        $action = new SyncDataScopesToUser();
        $result = $action->handle([
            'data_scope_ids' => [$scope2->id],
        ], $user->id);

        $this->assertEquals(1, $result->directDataScopes()->count());
        $this->assertEquals($scope2->id, $result->directDataScopes()->first()->id);
    }

    #[Test]
    public function it_can_revoke_data_scope_from_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $user->directDataScopes()->attach($dataScope->id);

        $action = new RevokeDataScopesFromUser();
        $result = $action->handle([
            'data_scope_ids' => [$dataScope->id],
        ], $user->id);

        $this->assertEquals(0, $result->directDataScopes()->count());
    }
}

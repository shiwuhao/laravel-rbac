<?php

namespace Rbac\Tests\Unit\Models;

use Rbac\Tests\TestCase;
use Rbac\Models\DataScope;
use Rbac\Enums\DataScopeType;

class DataScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->defineDatabaseMigrations();
        $this->setUpUserTable();
    }

    /** @test */
    public function it_can_create_a_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => '全部数据',
            'type' => DataScopeType::ALL,
            'description' => '可查看全部数据',
        ]);

        $this->assertDatabaseHas(config('rbac.tables.data_scopes'), [
            'name' => '全部数据',
            'type' => DataScopeType::ALL->value,
        ]);
    }

    /** @test */
    public function it_can_create_custom_data_scope()
    {
        $dataScope = DataScope::create([
            'name' => '自定义范围',
            'type' => DataScopeType::CUSTOM,
            'config' => [
                'status' => 'active',
                'region' => ['beijing', 'shanghai'],
            ],
        ]);

        $this->assertIsArray($dataScope->config);
        $this->assertEquals('active', $dataScope->config['status']);
    }

    /** @test */
    public function it_has_relationships()
    {
        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $dataScope->permissions());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $dataScope->users());
    }

    /** @test */
    public function it_can_use_type_scope()
    {
        DataScope::create([
            'name' => '全部数据',
            'type' => DataScopeType::ALL,
        ]);

        DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        DataScope::create([
            'name' => '部门数据',
            'type' => DataScopeType::DEPARTMENT,
        ]);

        $this->assertEquals(1, DataScope::byType(DataScopeType::ALL)->count());
        $this->assertEquals(1, DataScope::byType(DataScopeType::PERSONAL)->count());
        $this->assertEquals(1, DataScope::byType('department')->count());
    }

    /** @test */
    public function it_casts_type_to_enum()
    {
        $dataScope = DataScope::create([
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL,
        ]);

        $this->assertInstanceOf(DataScopeType::class, $dataScope->type);
        $this->assertEquals(DataScopeType::PERSONAL, $dataScope->type);
    }
}

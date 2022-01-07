<?php

namespace Shiwuhao\Rbac\Tests\Databases;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Shiwuhao\Rbac\Tests\TestCase;

/**
 *
 */
class MigrateDatabaseTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    /**
     *
     */
    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * @test
     */
    public function run_roles_migrations()
    {
        $tableName = Config::get('rbac.table.roles');

        $this->assertNotEmpty($tableName);

        $this->assertEquals([
            'id',
            'name',
            'label',
            'remark',
            'deleted_at',
            'created_at',
            'updated_at',
        ], Schema::getColumnListing($tableName));
    }

    /**
     * @test
     */
    public function run_actions_migrations()
    {
        $tableName = Config::get('rbac.table.actions');

        $this->assertNotEmpty($tableName);

        $this->assertEquals([
            'id',
            'name',
            'label',
            'method',
            'uri',
            'created_at',
            'updated_at',
        ], Schema::getColumnListing($tableName));
    }

    /**
     * @test
     */
    public function run_permissions_migrations()
    {
        $tableName = Config::get('rbac.table.permissions');

        $this->assertNotEmpty($tableName);

        $this->assertEquals([
            'id',
            'pid',
            'permissible_type',
            'permissible_id',
            'created_at',
            'updated_at',
        ], Schema::getColumnListing($tableName));
    }

    /**
     * @test
     */
    public function run_role_user_migrations()
    {
        $tableName = Config::get('rbac.table.role_user');

        $this->assertNotEmpty($tableName);

        $this->assertEquals([
            'role_id',
            'user_id',
        ], Schema::getColumnListing($tableName));
    }

    /**
     * @test
     */
    public function run_role_permission_migrations()
    {
        $tableName = Config::get('rbac.table.role_permission');

        $this->assertNotEmpty($tableName);

        $this->assertEquals([
            'role_id',
            'permission_id',
        ], Schema::getColumnListing($tableName));
    }
}
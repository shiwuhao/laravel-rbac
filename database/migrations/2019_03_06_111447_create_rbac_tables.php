<?php

use \Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class RbacSetupTables
 */
class CreateRbacTables extends Migration
{
    /**
     * @throws Exception
     */
    public function up()
    {
        $tableName = config('rbac.table');
        $foreignKey = config('rbac.foreignKey');

        DB::beginTransaction();

        Schema::create($tableName['roles'], function (Blueprint $table) {
            $table->id('id');
            $table->string('name')->comment('唯一标识');
            $table->string('title')->default('显示名称');
            $table->string('remark')->default('备注');
            $table->timestamps();
        });

        Schema::create($tableName['permissions'], function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('pid')->default(0);
            $table->string('name')->default('')->comment('唯一标识');
            $table->string('title')->default('')->comment('显示名称');
            $table->string('method')->default('')->comment('请求方式');
            $table->string('url')->default('')->comment('url');
            $table->string('remark')->default('')->comment('备注');
            $table->timestamps();

            $table->unique(['method', 'url']);
            $table->unique(['name']);
        });

        Schema::create($tableName['roleUser'], function (Blueprint $table) use ($tableName, $foreignKey) {
            $table->unsignedBigInteger($foreignKey['user']);
            $table->unsignedBigInteger($foreignKey['role']);

            $table->primary([$foreignKey['user'], $foreignKey['role']]);
        });

        Schema::create($tableName['permissionRole'], function (Blueprint $table) use ($tableName, $foreignKey) {
            $table->unsignedBigInteger($foreignKey['permission']);
            $table->unsignedBigInteger($foreignKey['role']);

            $table->primary([$foreignKey['permission'], $foreignKey['role']]);
        });

        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('rbac.table');

        Schema::drop($tableName['permissionRole']);
        Schema::drop($tableName['permissions']);
        Schema::drop($tableName['roleUser']);
        Schema::drop($tableName['roles']);
    }
}

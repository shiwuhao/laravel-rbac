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
            $table->string('name')->comment('角色唯一标识');
            $table->string('display_name')->default('角色显示名称');
            $table->string('description')->default('角色描述');
            $table->timestamps();
        });

        Schema::create($tableName['permissions'], function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('pid')->default(0);
            $table->string('name')->default('')->comment('节点唯一标识');
            $table->string('display_name')->default('')->comment('节点显示名称');
            $table->string('description')->default('')->comment('节点描述');
            $table->string('action')->default('');
            $table->timestamps();
        });

        Schema::create($tableName['roleUser'], function (Blueprint $table) use ($tableName, $foreignKey) {
            $table->unsignedBigInteger($foreignKey['user']);
            $table->unsignedBigInteger($foreignKey['role']);

            $table->foreign($foreignKey['user'])->references('id')->on($tableName['users'])->onUpdate('cascade')->onDelete('cascade');
            $table->foreign($foreignKey['role'])->references('id')->on($tableName['roles'])->onUpdate('cascade')->onDelete('cascade');
            $table->primary([$foreignKey['user'], $foreignKey['role']]);
        });

        Schema::create($tableName['permissionRole'], function (Blueprint $table) use ($tableName, $foreignKey) {
            $table->unsignedBigInteger($foreignKey['permission']);
            $table->unsignedBigInteger($foreignKey['role']);

            $table->foreign($foreignKey['permission'])->references('id')->on($tableName['permissions'])->onUpdate('cascade')->onDelete('cascade');
            $table->foreign($foreignKey['role'])->references('id')->on($tableName['roles'])->onUpdate('cascade')->onDelete('cascade');
            $table->primary([$foreignKey['permission'], $foreignKey['role']]);
        });

        Schema::create($tableName['permissionModel'], function (Blueprint $table) use ($tableName, $foreignKey) {
            $table->id('id');
            $table->unsignedBigInteger($foreignKey['role'])->comment('角色ID');
            $table->morphs('modelable');
            $table->timestamps();

            $table->foreign($foreignKey['role'])->references('id')->on($tableName['roles'])->onDelete('cascade');
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

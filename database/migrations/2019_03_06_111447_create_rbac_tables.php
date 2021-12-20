<?php

use Illuminate\Support\Facades\DB;
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

        DB::beginTransaction();

        // 角色表
        Schema::create($tableName['roles'], function (Blueprint $table) {
            $table->id('id');
            $table->string('name')->comment('唯一标识');
            $table->string('label')->default('显示名称');
            $table->string('remark')->default('备注');
            $table->timestamps();

            $table->unique('name');
        });

        // 菜单表
        Schema::create($tableName['menus'], function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('pid')->default(0);
            $table->string('name', 50)->default('')->comment('唯一标识');
            $table->string('label')->default('')->comment('显示名称');
            $table->string('icon', 50)->default('')->comment('图标');
            $table->string('url')->default('')->comment('页面url');
            $table->string('remark')->default('')->comment('备注');
            $table->timestamps();

            $table->unique('name');
        });

        // 操作表
        Schema::create($tableName['action'], function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 50)->default('')->comment('唯一标识');
            $table->string('label')->default('')->comment('显示名称');
            $table->string('method')->default('GET')->comment('请求方式');
            $table->string('url')->default('')->comment('请求路径');
            $table->timestamps();

            $table->unique('name');
            $table->unique(['method', 'url']);
        });

        // 权限表
        Schema::create($tableName['permissions'], function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('pid')->default(0)->comment('父级ID');
            $table->string('name')->default('')->comment('唯一标识');
            $table->string('title')->default('')->comment('显示名称');
            $table->morphs('permissible');
            $table->timestamps();

            $table->unique('name');
        });

        // 角色对应用户
        Schema::create($tableName['role_user'], function (Blueprint $table) use ($tableName) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');

            $table->primary(['role_id', 'user_id']);
        });

        // 角色对应权限
        Schema::create($tableName['role_permission'], function (Blueprint $table) use ($tableName) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->primary(['role_id', 'permission_id']);
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

        Schema::drop($tableName['roles']);
        Schema::drop($tableName['menus']);
        Schema::drop($tableName['actions']);
        Schema::drop($tableName['permissions']);
        Schema::drop($tableName['role_user']);
        Schema::drop($tableName['role_permission']);
    }
}

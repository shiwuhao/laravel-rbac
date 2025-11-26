<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = config('rbac.tables');

        // 角色表
        Schema::create($tables['roles'], function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('角色名称');
            $table->string('slug', 100)->comment('角色标识符');
            $table->text('description')->nullable()->comment('角色描述');
            $table->string('guard_name', 50)->default('web')->comment('守卫名称');
            $table->timestamps();
            $table->softDeletes();

            // 索引优化
            $table->unique(['slug', 'guard_name']);
            $table->index('guard_name');
        });

        // 权限表
        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('权限名称');
            $table->string('slug', 100)->comment('权限标识符');
            $table->text('description')->nullable()->comment('权限描述');
            $table->string('resource', 100)->nullable()->comment('资源标识');
            $table->string('action', 50)->nullable()->comment('操作类型');
            $table->string('resource_type', 100)->nullable()->comment('资源模型类型（多态）');
            $table->unsignedBigInteger('resource_id')->nullable()->comment('资源实例ID（多态）');
            $table->string('guard_name', 50)->default('web')->comment('守卫名称');
            $table->json('metadata')->nullable()->comment('元数据');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['slug', 'guard_name', 'resource_type', 'resource_id'], 'uniq_permissions_slug_guard_instance');
            $table->index(['resource', 'action']);
            $table->index(['resource_type', 'resource_id'], 'idx_resource_instance');
            $table->index('guard_name');
        });

        // 数据范围表
        Schema::create($tables['data_scopes'], function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('数据范围名称');
            $table->string('type', 50)->comment('范围类型');
            $table->json('config')->nullable()->comment('范围配置');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();

            $table->index('type');
        });

        // 角色权限关联表
        Schema::create($tables['role_permission'], function (Blueprint $table) use ($tables) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('role_id')
                ->references('id')
                ->on($tables['roles'])
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tables['permissions'])
                ->onDelete('cascade');

            $table->primary(['role_id', 'permission_id']);
        });

        // 用户角色关联表
        Schema::create($tables['user_role'], function (Blueprint $table) use ($tables) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('role_id')
                ->references('id')
                ->on($tables['roles'])
                ->onDelete('cascade');

            $table->primary(['user_id', 'role_id']);
            $table->index('user_id');
        });

        // 用户直接权限关联表
        Schema::create($tables['user_permission'], function (Blueprint $table) use ($tables) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('permission_id')
                ->references('id')
                ->on($tables['permissions'])
                ->onDelete('cascade');

            $table->primary(['user_id', 'permission_id']);
            $table->index('user_id');
        });

        // 权限数据范围关联表
        Schema::create($tables['permission_data_scope'], function (Blueprint $table) use ($tables) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('data_scope_id');
            $table->text('constraint')->nullable()->comment('额外约束条件');
            $table->timestamps();

            $table->foreign('permission_id')
                ->references('id')
                ->on($tables['permissions'])
                ->onDelete('cascade');

            $table->foreign('data_scope_id')
                ->references('id')
                ->on($tables['data_scopes'])
                ->onDelete('cascade');

            $table->primary(['permission_id', 'data_scope_id']);
        });

        // 角色数据范围关联表
        Schema::create($tables['role_data_scope'], function (Blueprint $table) use ($tables) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('data_scope_id');
            $table->text('constraint')->nullable()->comment('额外约束条件');
            $table->timestamps();

            $table->foreign('role_id')
                ->references('id')
                ->on($tables['roles'])
                ->onDelete('cascade');

            $table->foreign('data_scope_id')
                ->references('id')
                ->on($tables['data_scopes'])
                ->onDelete('cascade');

            $table->primary(['role_id', 'data_scope_id']);
        });

        // 用户数据范围关联表
        Schema::create($tables['user_data_scope'], function (Blueprint $table) use ($tables) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('data_scope_id');
            $table->text('constraint')->nullable()->comment('额外约束条件');
            $table->timestamps();

            $table->foreign('data_scope_id')
                ->references('id')
                ->on($tables['data_scopes'])
                ->onDelete('cascade');

            $table->primary(['user_id', 'data_scope_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = config('rbac.tables');

        Schema::dropIfExists($tables['user_data_scope']);
        Schema::dropIfExists($tables['role_data_scope']);
        Schema::dropIfExists($tables['permission_data_scope']);
        Schema::dropIfExists($tables['user_permission']);
        Schema::dropIfExists($tables['user_role']);
        Schema::dropIfExists($tables['role_permission']);
        Schema::dropIfExists($tables['data_scopes']);
        Schema::dropIfExists($tables['permissions']);
        Schema::dropIfExists($tables['roles']);
    }
};

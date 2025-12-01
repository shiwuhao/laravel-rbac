<?php

namespace Rbac\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 数据库填充器
 * 
 * 使用示例：
 * 
 * 1. 填充所有数据：
 *    php artisan db:seed
 * 
 * 2. 填充指定类型的数据（在 run 方法中使用）：
 *    $this->call(RbacSeeder::class, false, ['types' => ['data-scopes']]);
 *    $this->call(RbacSeeder::class, false, ['types' => ['data-scopes', 'roles']]);
 * 
 * 3. 可用的类型标识：
 *    - data-scopes: 仅填充数据范围
 *    - roles: 仅填充角色
 *    - permissions: 仅填充权限
 *    - assign-permissions: 仅分配权限给角色
 *    - assign-data-scopes: 仅分配数据范围给权限
 *    - all: 填充全部（默认）
 * 
 * 注意：
 * - 也可以使用 php artisan rbac:seed 命令进行填充
 * - 命令行方式更灵活，支持更多选项
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 运行数据填充
     */
    public function run(): void
    {
        // 填充所有 RBAC 数据
        $this->call(RbacSeeder::class);
        
        // 示例：仅填充数据范围
        // $this->call(RbacSeeder::class, false, ['types' => ['data-scopes']]);
        
        // 示例：填充数据范围和角色
        // $this->call(RbacSeeder::class, false, ['types' => ['data-scopes', 'roles']]);
    }
}
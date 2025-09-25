<?php

namespace Rbac\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 数据库填充器
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 运行数据填充
     */
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
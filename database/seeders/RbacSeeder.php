<?php

namespace Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Rbac\Support\SeedsRbacData;

/**
 * RBAC 数据填充器
 * 
 * 支持通过 call 方法传入参数来选择性填充不同类型的数据
 */
class RbacSeeder extends Seeder
{
    use SeedsRbacData;

    /**
     * 运行数据填充
     * 
     * 支持的类型参数：
     * - data-scopes: 数据范围
     * - roles: 角色
     * - permissions: 权限
     * - assign-permissions: 分配权限给角色
     * - assign-data-scopes: 分配数据范围给权限
     * - all: 全部（默认）
     * 
     * 使用示例：
     * $this->call(RbacSeeder::class, false, ['types' => ['data-scopes']]);
     * $this->call(RbacSeeder::class, false, ['types' => ['data-scopes', 'roles']]);
     */
    public function run(array $types = ['all']): void
    {
        // 调用 Trait 中的填充逻辑
        $this->runSeeding($types);
    }

    /**
     * 实现 Trait 的抽象方法
     */
    protected function log(string $message): void
    {
        $this->command->info($message);
    }
}

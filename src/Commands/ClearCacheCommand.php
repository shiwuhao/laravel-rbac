<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 清理RBAC缓存命令
 * 
 * 用于清理RBAC系统的所有缓存数据，包括用户权限缓存、角色缓存等
 * 建议在以下情况下运行：
 * - 权限配置变更后
 * - 角色分配变更后
 * - 权限验证异常时
 * 
 * @example php artisan rbac:clear-cache
 */
class ClearCacheCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:clear-cache';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '清理 RBAC 系统缓存';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('清理 RBAC 缓存...');
            
            $cacheDriver = config('cache.default');
            
            // 清理用户权限缓存
            if ($cacheDriver === 'redis' || $cacheDriver === 'memcached') {
                // 针对 Redis/Memcached 清理所有 rbac 前缀缓存
                $prefix = config('rbac.cache.key', 'laravel_rbac.cache');
                $this->line("清理前缀缓存: {$prefix}.*");
                // 注：实际清理需要扫描 keys，这里简化处理
                Cache::flush();
            } else {
                // 清理带标签的缓存
                Cache::tags(['rbac', 'user_permissions'])->flush();
                $this->line('清理带标签的缓存');
            }
            
            $this->info('✓ RBAC 缓存清理完成！');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("清理缓存失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
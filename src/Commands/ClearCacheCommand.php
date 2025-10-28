<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Actions\Permission\ClearAllCache;

/**
 * 清理缓存命令
 */
class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理 RBAC 系统缓存';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('清理 RBAC 缓存...');
            
            ClearAllCache::handle();
            
            $this->info('RBAC 缓存清理完成！');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("清理缓存失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
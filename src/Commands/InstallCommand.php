<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * 安装 RBAC 系统命令
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:install 
                            {--force : 强制重新安装}
                            {--seed : 同时运行数据填充}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '安装 RBAC 系统';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');
        $seed = $this->option('seed');

        try {
            $this->info('开始安装 RBAC 系统...');

            // 发布配置文件
            $this->publishConfig($force);

            // 发布迁移文件
            $this->publishMigrations($force);

            // 发布数据填充文件
            $this->publishSeeders($force);

            // 运行迁移
            $this->runMigrations();

            // 运行数据填充
            if ($seed) {
                $this->runSeeders();
            }

            $this->info('');
            $this->info('🎉 RBAC 系统安装完成！');
            $this->info('');
            
            if ($seed) {
                $this->info('💡 下一步：');
                $this->line('  • 使用 php artisan rbac:status 查看系统状态');
                $this->line('  • 使用 php artisan rbac:list-permissions 查看所有权限');
                $this->line('  • 访问 /api/rbac/roles 查看角色列表');
            } else {
                $this->info('💡 下一步：');
                $this->line('  • 运行 php artisan rbac:seed 填充测试数据');
                $this->line('  • 运行 php artisan rbac:seed --type=data-scopes 仅填充数据范围');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("安装失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 发布配置文件
     */
    protected function publishConfig(bool $force): void
    {
        $this->info('发布配置文件...');
        
        $params = ['--provider' => 'Rbac\\RbacServiceProvider', '--tag' => 'rbac-config'];
        if ($force) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', $params);
    }

    /**
     * 发布迁移文件
     */
    protected function publishMigrations(bool $force): void
    {
        $this->info('发布迁移文件...');
        
        $params = ['--provider' => 'Rbac\\RbacServiceProvider', '--tag' => 'rbac-migrations'];
        if ($force) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', $params);
    }

    /**
     * 发布数据填充文件
     */
    protected function publishSeeders(bool $force): void
    {
        $this->info('发布数据填充文件...');
        
        $params = ['--provider' => 'Rbac\\RbacServiceProvider', '--tag' => 'rbac-seeders'];
        if ($force) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', $params);
    }

    /**
     * 运行迁移
     */
    protected function runMigrations(): void
    {
        $this->info('运行数据库迁移...');
        
        if ($this->confirm('是否运行数据库迁移？', true)) {
            Artisan::call('migrate');
            $this->info('数据库迁移完成');
        }
    }

    /**
     * 运行数据填充
     */
    protected function runSeeders(): void
    {
        $this->info('运行数据填充...');
        
        if ($this->confirm('是否填充 RBAC 测试数据？', true)) {
            Artisan::call('rbac:seed', ['--force' => true], $this->getOutput());
            $this->info('✓ 数据填充完成');
        }
    }
}
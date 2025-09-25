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
                            {--seed : 同时运行测试数据填充}
                            {--demo : 包含演示数据}';

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
        $demo = $this->option('demo');

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
                $this->runSeeders($demo);
            }

            $this->info('RBAC 系统安装完成！');
            
            if ($seed && $demo) {
                $this->info('');
                $this->info('演示用户已创建，可以使用以下账户登录：');
                $this->table(
                    ['角色', '邮箱', '密码'],
                    [
                        ['超级管理员', 'superadmin@example.com', 'password'],
                        ['管理员', 'admin@example.com', 'password'],
                        ['编辑', 'editor@example.com', 'password'],
                        ['普通用户', 'user@example.com', 'password'],
                    ]
                );
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
    protected function runSeeders(bool $includeDemo): void
    {
        $this->info('运行数据填充...');
        
        // 运行基础数据填充
        Artisan::call('db:seed', ['--class' => 'Rbac\\Database\\Seeders\\RbacSeeder']);
        $this->info('基础数据填充完成');

        // 运行演示数据填充
        if ($includeDemo) {
            if ($this->confirm('是否创建演示数据？', false)) {
                Artisan::call('db:seed', ['--class' => 'Rbac\\Database\\Seeders\\DemoDataSeeder']);
                $this->info('演示数据填充完成');
            }
        }
    }
}
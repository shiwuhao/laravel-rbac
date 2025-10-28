<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;
use Rbac\Actions\Role\CreateRole;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Actions\DataScope\CreateDataScope;
use Rbac\Actions\Role\AssignRolePermissions;

/**
 * 快速填充基础RBAC数据命令
 * 
 * 用于快速创建基础的角色、权限和数据范围
 * 适合开发环境快速搭建测试数据
 */
class QuickSeedCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:quick-seed
                            {--demo : 包含演示数据}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '快速填充基础RBAC数据（角色、权限、数据范围）';

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $demo = $this->option('demo');

        try {
            $this->info('开始填充基础RBAC数据...');

            // 创建基础角色
            $roles = $this->createBasicRoles();
            $this->info('✓ 基础角色创建完成');

            // 创建基础权限
            $permissions = $this->createBasicPermissions();
            $this->info('✓ 基础权限创建完成');

            // 创建数据范围
            $dataScopes = $this->createBasicDataScopes();
            $this->info('✓ 数据范围创建完成');

            // 分配权限
            $this->assignBasicPermissions($roles, $permissions);
            $this->info('✓ 权限分配完成');

            if ($demo) {
                $this->createDemoData();
                $this->info('✓ 演示数据创建完成');
            }

            $this->displayQuickSummary($roles, $permissions, $dataScopes);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("填充数据失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 创建基础角色
     */
    protected function createBasicRoles(): array
    {
        $roles = [
            ['name' => '超级管理员', 'slug' => 'super-admin', 'description' => '系统超级管理员'],
            ['name' => '管理员', 'slug' => 'admin', 'description' => '系统管理员'],
            ['name' => '用户', 'slug' => 'user', 'description' => '普通用户'],
        ];

        $createdRoles = [];
        foreach ($roles as $role) {
            $createdRoles[] = CreateRole::handle([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'guard_name' => GuardType::WEB->value
            ]);
        }

        return $createdRoles;
    }

    /**
     * 创建基础权限
     */
    protected function createBasicPermissions(): array
    {
        $resources = ['user', 'role', 'permission'];
        $actions = [ActionType::VIEW, ActionType::CREATE, ActionType::UPDATE, ActionType::DELETE];

        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = CreatePermission::handle([
                    'name' => ucfirst($action->value) . ' ' . ucfirst($resource),
                    'slug' => $resource . '.' . $action->value,
                    'resource' => $resource,
                    'action' => $action->value,
                    'description' => ucfirst($action->value) . ' ' . ucfirst($resource) . ' permission',
                    'guard_name' => GuardType::WEB->value
                ]);
            }
        }

        return $permissions;
    }

    /**
     * 创建基础数据范围
     */
    protected function createBasicDataScopes(): array
    {
        $scopes = [
            ['name' => '全部数据', 'type' => DataScopeType::ALL],
            ['name' => '个人数据', 'type' => DataScopeType::PERSONAL],
        ];

        $createdScopes = [];
        foreach ($scopes as $scope) {
            $createdScopes[] = CreateDataScope::handle([
                'name' => $scope['name'],
                'type' => $scope['type']->value,
                'config' => [],
                'description' => $scope['name'] . '范围'
            ]);
        }

        return $createdScopes;
    }

    /**
     * 分配基础权限
     */
    protected function assignBasicPermissions(array $roles, array $permissions): void
    {
        // 超级管理员获得所有权限
        $superAdmin = collect($roles)->firstWhere('slug', 'super-admin');
        $permissionIds = collect($permissions)->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $permissionIds, 'replace' => true], $superAdmin->id);

        // 管理员获得用户相关权限
        $admin = collect($roles)->firstWhere('slug', 'admin');
        $adminPermissions = collect($permissions)->filter(function ($permission) {
            return $permission->resource === 'user';
        });
        $adminPermissionIds = $adminPermissions->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $adminPermissionIds, 'replace' => true], $admin->id);

        // 普通用户只有查看权限
        $user = collect($roles)->firstWhere('slug', 'user');
        $userPermissions = collect($permissions)->filter(function ($permission) {
            return $permission->action === 'view';
        });
        $userPermissionIds = $userPermissions->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $userPermissionIds, 'replace' => true], $user->id);
    }

    /**
     * 创建演示数据
     */
    protected function createDemoData(): void
    {
        // 创建更多角色
        CreateRole::handle(['name' => '编辑', 'slug' => 'editor', 'description' => '内容编辑', 'guard_name' => GuardType::WEB->value]);
        CreateRole::handle(['name' => '访客', 'slug' => 'guest', 'description' => '访客用户', 'guard_name' => GuardType::WEB->value]);

        // 创建更多权限
        CreatePermission::handle([
            'name' => '导出数据',
            'slug' => 'data.export',
            'resource' => 'data',
            'action' => ActionType::VIEW->value,
            'description' => '导出系统数据',
            'guard_name' => GuardType::WEB->value
        ]);

        CreatePermission::handle([
            'name' => '系统设置',
            'slug' => 'system.setting',
            'resource' => 'system',
            'action' => ActionType::UPDATE->value,
            'description' => '修改系统设置',
            'guard_name' => GuardType::WEB->value
        ]);
    }

    /**
     * 显示快速摘要
     */
    protected function displayQuickSummary(array $roles, array $permissions, array $dataScopes): void
    {
        $this->info('');
        $this->info('🎉 基础RBAC数据填充完成！');
        $this->info('');
        
        $this->table(['项目', '数量'], [
            ['角色', count($roles)],
            ['权限', count($permissions)],
            ['数据范围', count($dataScopes)],
        ]);

        $this->info('');
        $this->info('创建的角色：');
        foreach ($roles as $role) {
            $this->line("  • {$role->name} ({$role->slug})");
        }

        $this->info('');
        $this->info('💡 下一步：');
        $this->info('  - 运行 php artisan rbac:seed-test-data --users 创建完整测试数据');
        $this->info('  - 或运行 php artisan rbac:quick-seed --demo 包含演示数据');
    }
}
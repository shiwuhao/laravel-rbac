<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;
use Rbac\Enums\DataScopeType;
use Illuminate\Support\Facades\DB;
use Rbac\Actions\DataScope\CreateDataScope;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Actions\Role\CreateRole;
use Rbac\Actions\Role\AssignRolePermissions;
use Rbac\Actions\Permission\AssignDataScopeToPermission;
use Rbac\Actions\UserPermission\AssignRoleToUser;

/**
 * 填充RBAC测试数据命令
 * 
 * 用于创建完整的测试数据，包括角色、权限、数据范围等
 * 支持强制重建和清理数据
 */
class SeedTestDataCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:seed-test-data
                            {--force : 强制重新创建数据（清空现有数据）}
                            {--users : 同时创建测试用户}
                            {--clean : 仅清空数据不创建}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '填充RBAC系统测试数据';

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
        $force = $this->option('force');
        $createUsers = $this->option('users');
        $cleanOnly = $this->option('clean');

        try {
            if ($force || $cleanOnly) {
                $this->cleanExistingData();
            }

            if ($cleanOnly) {
                $this->info('数据清理完成！');
                return Command::SUCCESS;
            }

            $this->info('开始填充RBAC测试数据...');

            // 创建数据范围
            $dataScopes = $this->createDataScopes();
            $this->info('✓ 数据范围创建完成');

            // 创建权限
            $permissions = $this->createPermissions();
            $this->info('✓ 权限节点创建完成');

            // 创建角色
            $roles = $this->createRoles();
            $this->info('✓ 角色创建完成');

            // 分配权限给角色
            $this->assignPermissionsToRoles($roles, $permissions);
            $this->info('✓ 角色权限分配完成');

            // 分配数据范围给权限
            $this->assignDataScopesToPermissions($permissions, $dataScopes);
            $this->info('✓ 权限数据范围分配完成');

            // 创建测试用户（可选）
            if ($createUsers) {
                $users = $this->createTestUsers();
                $this->assignRolesToUsers($users, $roles);
                $this->info('✓ 测试用户创建完成');
            }

            $this->displaySummary($roles, $permissions, $dataScopes, $createUsers);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("填充测试数据失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 清空现有数据
     */
    protected function cleanExistingData(): void
    {
        $this->warn('清理现有RBAC数据...');

        if (!$this->confirm('确定要清空所有RBAC数据吗？此操作不可逆！', false)) {
            $this->info('操作已取消');
            return;
        }

        DB::beginTransaction();
        try {
            // 清空关联表
            DB::table('role_permission')->delete();
            DB::table('user_role')->delete();
            DB::table('user_permission')->delete();
            DB::table('permission_data_scope')->delete();
            DB::table('user_data_scope')->delete();

            // 清空主表
            DB::table('roles')->delete();
            DB::table('permissions')->delete();
            DB::table('data_scopes')->delete();

            DB::commit();
            $this->info('✓ 现有数据已清理');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * 创建数据范围
     */
    protected function createDataScopes(): array
    {
        $scopes = [
            [
                'name' => '个人数据',
                'type' => DataScopeType::PERSONAL,
                'config' => ['field' => 'user_id'],
                'description' => '只能查看自己创建的数据'
            ],
            [
                'name' => '部门数据',
                'type' => DataScopeType::DEPARTMENT,
                'config' => ['field' => 'department_id'],
                'description' => '可以查看本部门的数据'
            ],
            [
                'name' => '全部数据',
                'type' => DataScopeType::ALL,
                'config' => [],
                'description' => '可以查看所有数据'
            ],
            [
                'name' => '自定义数据',
                'type' => DataScopeType::CUSTOM,
                'config' => ['rules' => ['status' => 'active']],
                'description' => '根据自定义规则过滤数据'
            ]
        ];

        $createdScopes = [];
        foreach ($scopes as $scope) {
            $createdScopes[] = CreateDataScope::handle([
                'name' => $scope['name'],
                'type' => $scope['type']->value,
                'config' => $scope['config'],
                'description' => $scope['description']
            ]);
        }

        return $createdScopes;
    }

    /**
     * 创建权限
     */
    protected function createPermissions(): array
    {
        $resources = [
            'user' => '用户管理',
            'role' => '角色管理',
            'permission' => '权限管理',
            'department' => '部门管理',
            'report' => '报表管理',
            'system' => '系统管理'
        ];

        $actions = [
            ActionType::VIEW->value => '查看',
            ActionType::CREATE->value => '创建',
            ActionType::UPDATE->value => '更新',
            ActionType::DELETE->value => '删除'
        ];

        $permissions = [];
        foreach ($resources as $resource => $resourceName) {
            foreach ($actions as $action => $actionName) {
                $permission = CreatePermission::handle([
                    'name' => $actionName . $resourceName,
                    'slug' => $resource . '.' . $action,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $actionName . $resourceName . '权限',
                    'guard_name' => GuardType::WEB->value
                ]);
                $permissions[] = $permission;
            }
        }

        // 添加特殊权限
        $specialPermissions = [
            [
                'name' => '导出数据',
                'slug' => 'data.export',
                'resource' => 'data',
                'action' => ActionType::VIEW,
                'description' => '导出各类数据权限'
            ],
            [
                'name' => '数据统计',
                'slug' => 'data.statistics',
                'resource' => 'data',
                'action' => ActionType::VIEW,
                'description' => '查看数据统计信息'
            ]
        ];

        foreach ($specialPermissions as $perm) {
            $permissions[] = CreatePermission::handle([
                'name' => $perm['name'],
                'slug' => $perm['slug'],
                'resource' => $perm['resource'],
                'action' => $perm['action']->value,
                'description' => $perm['description'],
                'guard_name' => GuardType::WEB->value
            ]);
        }

        return $permissions;
    }

    /**
     * 创建角色
     */
    protected function createRoles(): array
    {
        $roles = [
            [
                'name' => '超级管理员',
                'slug' => 'super-admin',
                'description' => '拥有系统所有权限的超级管理员'
            ],
            [
                'name' => '系统管理员',
                'slug' => 'admin',
                'description' => '负责系统管理的管理员'
            ],
            [
                'name' => '部门经理',
                'slug' => 'manager',
                'description' => '部门经理，管理本部门事务'
            ],
            [
                'name' => '普通员工',
                'slug' => 'employee',
                'description' => '普通员工，基础操作权限'
            ],
            [
                'name' => '访客',
                'slug' => 'guest',
                'description' => '访客用户，只有查看权限'
            ]
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
     * 分配权限给角色
     */
    protected function assignPermissionsToRoles(array $roles, array $permissions): void
    {
        // 超级管理员：所有权限
        $superAdmin = collect($roles)->firstWhere('slug', 'super-admin');
        $permissionIds = collect($permissions)->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $permissionIds, 'replace' => true], $superAdmin->id);

        // 系统管理员：除删除系统管理外的所有权限
        $admin = collect($roles)->firstWhere('slug', 'admin');
        $adminPermissions = collect($permissions)->reject(function ($permission) {
            return $permission->resource === 'system' && $permission->action === 'delete';
        });
        $adminPermissionIds = $adminPermissions->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $adminPermissionIds, 'replace' => true], $admin->id);

        // 部门经理：用户、部门、报表相关权限
        $manager = collect($roles)->firstWhere('slug', 'manager');
        $managerResources = ['user', 'department', 'report', 'data'];
        $managerPermissions = collect($permissions)->filter(function ($permission) use ($managerResources) {
            return in_array($permission->resource, $managerResources);
        });
        $managerPermissionIds = $managerPermissions->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $managerPermissionIds, 'replace' => true], $manager->id);

        // 普通员工：基础查看和个人数据权限
        $employee = collect($roles)->firstWhere('slug', 'employee');
        $employeePermissions = collect($permissions)->filter(function ($permission) {
            return in_array($permission->action, ['view']) ||
                   ($permission->resource === 'user' && $permission->action === 'update');
        });
        $employeePermissionIds = $employeePermissions->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $employeePermissionIds, 'replace' => true], $employee->id);

        // 访客：只有查看权限
        $guest = collect($roles)->firstWhere('slug', 'guest');
        $guestPermissions = collect($permissions)->filter(function ($permission) {
            return $permission->action === 'view' &&
                   in_array($permission->resource, ['user', 'department', 'report']);
        });
        $guestPermissionIds = $guestPermissions->pluck('id')->toArray();
        AssignRolePermissions::handle(['permission_ids' => $guestPermissionIds, 'replace' => true], $guest->id);
    }

    /**
     * 分配数据范围给权限
     */
    protected function assignDataScopesToPermissions(array $permissions, array $dataScopes): void
    {
        $allScope = collect($dataScopes)->firstWhere('type', DataScopeType::ALL->value);
        $deptScope = collect($dataScopes)->firstWhere('type', DataScopeType::DEPARTMENT->value);
        $personalScope = collect($dataScopes)->firstWhere('type', DataScopeType::PERSONAL->value);

        foreach ($permissions as $permission) {
            // 根据权限类型分配不同的数据范围
            switch ($permission->resource) {
                case 'user':
                case 'department':
                    // 用户和部门数据支持所有数据范围
                    AssignDataScopeToPermission::handle(['data_scope_id' => $allScope->id], $permission->id);
                    AssignDataScopeToPermission::handle(['data_scope_id' => $deptScope->id], $permission->id);
                    AssignDataScopeToPermission::handle(['data_scope_id' => $personalScope->id], $permission->id);
                    break;

                case 'report':
                    // 报表数据支持全部和部门范围
                    AssignDataScopeToPermission::handle(['data_scope_id' => $allScope->id], $permission->id);
                    AssignDataScopeToPermission::handle(['data_scope_id' => $deptScope->id], $permission->id);
                    break;

                default:
                    // 其他资源默认个人范围
                    AssignDataScopeToPermission::handle(['data_scope_id' => $personalScope->id], $permission->id);
                    break;
            }
        }
    }

    /**
     * 创建测试用户
     */
    protected function createTestUsers(): array
    {
        if (!class_exists('App\Models\User')) {
            $this->warn('User模型不存在，跳过用户创建');
            return [];
        }

        $users = [];
        $testUsers = [
            [
                'name' => '超级管理员',
                'email' => 'superadmin@example.com',
                'password' => bcrypt('password'),
                'role' => 'super-admin'
            ],
            [
                'name' => '系统管理员',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ],
            [
                'name' => '部门经理',
                'email' => 'manager@example.com',
                'password' => bcrypt('password'),
                'role' => 'manager'
            ],
            [
                'name' => '普通员工',
                'email' => 'employee@example.com',
                'password' => bcrypt('password'),
                'role' => 'employee'
            ]
        ];

        foreach ($testUsers as $userData) {
            $user = \App\Models\User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_verified_at' => now(),
            ]);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * 分配角色给用户
     */
    protected function assignRolesToUsers(array $users, array $roles): void
    {
        $roleMap = [
            'superadmin@example.com' => 'super-admin',
            'admin@example.com' => 'admin',
            'manager@example.com' => 'manager',
            'employee@example.com' => 'employee'
        ];

        foreach ($users as $user) {
            if (isset($roleMap[$user->email])) {
                $role = collect($roles)->firstWhere('slug', $roleMap[$user->email]);
                if ($role) {
                    AssignRoleToUser::handle(['role_id' => $role->id], $user->id);
                }
            }
        }
    }

    /**
     * 显示创建结果摘要
     */
    protected function displaySummary(array $roles, array $permissions, array $dataScopes, bool $usersCreated): void
    {
        $this->info('');
        $this->info('🎉 测试数据创建完成！');
        $this->info('');

        $this->table(['类型', '数量'], [
            ['角色', count($roles)],
            ['权限', count($permissions)],
            ['数据范围', count($dataScopes)],
            ['测试用户', $usersCreated ? '4个' : '未创建'],
        ]);

        if ($usersCreated) {
            $this->info('');
            $this->info('测试用户账户：');
            $this->table(['角色', '邮箱', '密码'], [
                ['超级管理员', 'superadmin@example.com', 'password'],
                ['系统管理员', 'admin@example.com', 'password'],
                ['部门经理', 'manager@example.com', 'password'],
                ['普通员工', 'employee@example.com', 'password'],
            ]);
        }

        $this->info('');
        $this->info('💡 提示：');
        $this->info('  - 使用 php artisan rbac:seed-test-data --clean 清空数据');
        $this->info('  - 使用 php artisan rbac:seed-test-data --users 同时创建测试用户');
        $this->info('  - 使用 php artisan rbac:seed-test-data --force 强制重新创建');
    }
}
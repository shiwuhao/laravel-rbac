<?php

namespace Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Actions\UserPermission\AssignRoleToUser;
use Rbac\Actions\UserPermission\AssignPermissionToUser;
use Rbac\Actions\UserPermission\AssignDataScopeToUser;

/**
 * 演示数据填充器
 * 创建用户并分配角色权限
 */
class DemoDataSeeder extends Seeder
{

    /**
     * 运行数据填充
     */
    public function run(): void
    {
        $this->createDemoUsers();
        $this->assignRolesToUsers();
        $this->assignDirectPermissions();
        $this->assignDataScopesToUsers();
        
        $this->command->info('演示数据创建完成！');
        $this->command->info('演示用户登录信息：');
        $this->command->table(
            ['用户名', '邮箱', '密码', '角色'],
            [
                ['超级管理员', 'superadmin@example.com', 'password', '超级管理员'],
                ['张管理', 'admin@example.com', 'password', '管理员'],
                ['李编辑', 'editor@example.com', 'password', '编辑'],
                ['王作者', 'author@example.com', 'password', '作者'],
                ['赵审核', 'reviewer@example.com', 'password', '审核员'],
                ['钱财务', 'finance@example.com', 'password', '财务'],
                ['孙人事', 'hr@example.com', 'password', '人事'],
                ['周客服', 'support@example.com', 'password', '客服'],
                ['吴用户', 'user@example.com', 'password', '用户'],
            ]
        );
    }

    /**
     * 创建演示用户
     */
    protected function createDemoUsers(): void
    {
        $this->command->info('创建演示用户...');

        $userModel = config('auth.providers.users.model');
        
        $users = [
            [
                'name' => '超级管理员',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 1,
            ],
            [
                'name' => '张管理',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 1,
            ],
            [
                'name' => '李编辑',
                'email' => 'editor@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 2,
            ],
            [
                'name' => '王作者',
                'email' => 'author@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 2,
            ],
            [
                'name' => '赵审核',
                'email' => 'reviewer@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 3,
            ],
            [
                'name' => '钱财务',
                'email' => 'finance@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 4,
            ],
            [
                'name' => '孙人事',
                'email' => 'hr@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 1,
                'department_id' => 5,
            ],
            [
                'name' => '周客服',
                'email' => 'support@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 2,
                'department_id' => 6,
            ],
            [
                'name' => '吴用户',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'organization_id' => 2,
                'department_id' => 7,
            ],
        ];

        foreach ($users as $userData) {
            // 检查邮箱是否已存在
            if (!$userModel::where('email', $userData['email'])->exists()) {
                $userModel::create($userData);
            }
        }
    }

    /**
     * 分配角色给用户
     */
    protected function assignRolesToUsers(): void
    {
        $this->command->info('分配角色给用户...');

        $userModel = config('auth.providers.users.model');
        
        $userRoleMapping = [
            'superadmin@example.com' => 'super-admin',
            'admin@example.com' => 'admin',
            'editor@example.com' => 'editor',
            'author@example.com' => 'author',
            'reviewer@example.com' => 'reviewer',
            'finance@example.com' => 'finance',
            'hr@example.com' => 'hr',
            'support@example.com' => 'support',
            'user@example.com' => 'user',
        ];

        foreach ($userRoleMapping as $email => $roleSlug) {
            $user = $userModel::where('email', $email)->first();
            $role = Role::where('slug', $roleSlug)->first();
            
            if ($user && $role) {
                AssignRoleToUser::handle(['role_id' => $role->id], $user->id);
            }
        }
    }

    /**
     * 分配直接权限
     */
    protected function assignDirectPermissions(): void
    {
        $this->command->info('分配直接权限...');

        $userModel = config('auth.providers.users.model');
        
        // 给编辑用户额外的文件管理权限
        $editor = $userModel::where('email', 'editor@example.com')->first();
        $fileManagePermission = Permission::where('slug', 'file.manage')->first();
        if ($editor && $fileManagePermission) {
            AssignPermissionToUser::handle(['permission_id' => $fileManagePermission->id], $editor->id);
        }

        // 给作者用户额外的文件上传权限
        $author = $userModel::where('email', 'author@example.com')->first();
        $fileCreatePermission = Permission::where('slug', 'file.create')->first();
        if ($author && $fileCreatePermission) {
            AssignPermissionToUser::handle(['permission_id' => $fileCreatePermission->id], $author->id);
        }

        // 给客服用户额外的用户查看权限
        $support = $userModel::where('email', 'support@example.com')->first();
        $userViewPermission = Permission::where('slug', 'user.view')->first();
        if ($support && $userViewPermission) {
            AssignPermissionToUser::handle(['permission_id' => $userViewPermission->id], $support->id);
        }
    }

    /**
     * 分配数据范围给用户
     */
    protected function assignDataScopesToUsers(): void
    {
        $this->command->info('分配数据范围给用户...');

        $userModel = config('auth.providers.users.model');
        
        // 给管理员分配组织数据范围
        $admin = $userModel::where('email', 'admin@example.com')->first();
        $orgDataScope = DataScope::where('type', 'organization')->first();
        if ($admin && $orgDataScope) {
            AssignDataScopeToUser::handle(['data_scope_id' => $orgDataScope->id], $admin->id);
        }

        // 给编辑分配部门数据范围
        $editor = $userModel::where('email', 'editor@example.com')->first();
        $deptDataScope = DataScope::where('type', 'department')->first();
        if ($editor && $deptDataScope) {
            AssignDataScopeToUser::handle(['data_scope_id' => $deptDataScope->id], $editor->id);
        }

        // 给作者分配个人数据范围
        $author = $userModel::where('email', 'author@example.com')->first();
        $personalDataScope = DataScope::where('type', 'personal')->first();
        if ($author && $personalDataScope) {
            AssignDataScopeToUser::handle(['data_scope_id' => $personalDataScope->id], $author->id);
        }

        // 给财务分配自定义数据范围
        $finance = $userModel::where('email', 'finance@example.com')->first();
        $customDataScope = DataScope::where('type', 'custom')->first();
        if ($finance && $customDataScope) {
            AssignDataScopeToUser::handle(['data_scope_id' => $customDataScope->id], $finance->id);
        }
    }
}
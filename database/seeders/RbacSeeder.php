<?php

namespace Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Rbac\Models\Role;
use Rbac\Models\Permission;
use Rbac\Models\DataScope;
use Rbac\Services\RbacService;
use Rbac\Enums\ActionType;
use Rbac\Enums\DataScopeType;

/**
 * RBAC 数据填充器
 */
class RbacSeeder extends Seeder
{
    protected RbacService $rbacService;

    public function __construct()
    {
        $this->rbacService = app(RbacService::class);
    }

    /**
     * 运行数据填充
     */
    public function run(): void
    {
        $this->createDataScopes();
        $this->createRoles();
        $this->createPermissions();
        $this->assignPermissionsToRoles();
        $this->assignDataScopesToPermissions();
        
        $this->command->info('RBAC 测试数据创建完成！');
    }

    /**
     * 创建数据范围
     */
    protected function createDataScopes(): void
    {
        $this->command->info('创建数据范围...');

        $dataScopes = [
            [
                'name' => '全部数据',
                'type' => DataScopeType::ALL,
                'config' => null,
                'description' => '可以访问系统中的所有数据',
            ],
            [
                'name' => '本组织数据',
                'type' => DataScopeType::ORGANIZATION,
                'config' => [
                    'organization_field' => 'organization_id',
                    'include_sub_organizations' => true,
                ],
                'description' => '只能访问本组织及下属组织的数据',
            ],
            [
                'name' => '本部门数据',
                'type' => DataScopeType::DEPARTMENT,
                'config' => [
                    'department_field' => 'department_id',
                    'include_sub_departments' => false,
                ],
                'description' => '只能访问本部门的数据',
            ],
            [
                'name' => '个人数据',
                'type' => DataScopeType::PERSONAL,
                'config' => [
                    'user_field' => 'user_id',
                    'creator_field' => 'created_by',
                ],
                'description' => '只能访问个人创建或负责的数据',
            ],
            [
                'name' => '活跃用户数据',
                'type' => DataScopeType::CUSTOM,
                'config' => [
                    'rules' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'active',
                        ],
                        [
                            'field' => 'last_login_at',
                            'operator' => '>=',
                            'value' => now()->subDays(30)->toDateString(),
                        ],
                    ],
                ],
                'description' => '只能访问30天内活跃的用户数据',
            ],
        ];

        foreach ($dataScopes as $dataScopeData) {
            $this->rbacService->createDataScope(
                $dataScopeData['name'],
                $dataScopeData['type'],
                $dataScopeData['config'],
                $dataScopeData['description']
            );
        }
    }

    /**
     * 创建角色
     */
    protected function createRoles(): void
    {
        $this->command->info('创建角色...');

        $roles = [
            [
                'name' => '超级管理员',
                'slug' => 'super-admin',
                'description' => '拥有系统所有权限的超级管理员',
            ],
            [
                'name' => '管理员',
                'slug' => 'admin',
                'description' => '系统管理员，拥有大部分管理权限',
            ],
            [
                'name' => '编辑',
                'slug' => 'editor',
                'description' => '内容编辑员，可以管理内容相关功能',
            ],
            [
                'name' => '作者',
                'slug' => 'author',
                'description' => '内容作者，可以创建和编辑自己的内容',
            ],
            [
                'name' => '审核员',
                'slug' => 'reviewer',
                'description' => '内容审核员，负责审核用户提交的内容',
            ],
            [
                'name' => '财务',
                'slug' => 'finance',
                'description' => '财务人员，管理财务相关数据',
            ],
            [
                'name' => '人事',
                'slug' => 'hr',
                'description' => '人事专员，管理员工信息',
            ],
            [
                'name' => '客服',
                'slug' => 'support',
                'description' => '客服人员，处理用户问题',
            ],
            [
                'name' => '用户',
                'slug' => 'user',
                'description' => '普通用户，拥有基础功能权限',
            ],
        ];

        foreach ($roles as $roleData) {
            $this->rbacService->createRole(
                $roleData['name'],
                $roleData['slug'],
                $roleData['description']
            );
        }
    }

    /**
     * 创建权限
     */
    protected function createPermissions(): void
    {
        $this->command->info('创建权限...');

        $resources = [
            'User' => '用户',
            'Role' => '角色',
            'Permission' => '权限',
            'DataScope' => '数据范围',
            'Post' => '文章',
            'Comment' => '评论',
            'Category' => '分类',
            'Tag' => '标签',
            'File' => '文件',
            'Setting' => '设置',
            'Log' => '日志',
            'Report' => '报表',
            'Order' => '订单',
            'Product' => '产品',
        ];

        $actions = [
            ActionType::VIEW,
            ActionType::CREATE,
            ActionType::UPDATE,
            ActionType::DELETE,
            ActionType::EXPORT,
            ActionType::IMPORT,
            ActionType::MANAGE,
        ];

        foreach ($resources as $resource => $resourceName) {
            foreach ($actions as $action) {
                // 某些资源不需要所有操作
                if ($this->shouldSkipPermission($resource, $action)) {
                    continue;
                }

                $this->rbacService->createPermission(
                    $action->label() . $resourceName,
                    strtolower($resource) . '.' . $action->value,
                    $resource,
                    $action,
                    "允许{$action->description()}: {$resourceName}"
                );
            }
        }

        // 创建一些特殊权限
        $specialPermissions = [
            [
                'name' => '系统监控',
                'slug' => 'system.monitor',
                'resource' => 'System',
                'action' => ActionType::VIEW,
                'description' => '查看系统监控信息',
            ],
            [
                'name' => '备份数据',
                'slug' => 'system.backup',
                'resource' => 'System',
                'action' => ActionType::EXPORT,
                'description' => '备份系统数据',
            ],
            [
                'name' => '清理缓存',
                'slug' => 'system.cache-clear',
                'resource' => 'System',
                'action' => ActionType::MANAGE,
                'description' => '清理系统缓存',
            ],
        ];

        foreach ($specialPermissions as $permission) {
            $this->rbacService->createPermission(
                $permission['name'],
                $permission['slug'],
                $permission['resource'],
                $permission['action'],
                $permission['description']
            );
        }
    }

    /**
     * 分配权限给角色
     */
    protected function assignPermissionsToRoles(): void
    {
        $this->command->info('分配权限给角色...');

        // 超级管理员拥有所有权限
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $allPermissions = Permission::all();
        $this->rbacService->syncRolePermissions($superAdmin, $allPermissions->pluck('id')->toArray());

        // 管理员权限
        $admin = Role::where('slug', 'admin')->first();
        $adminPermissions = Permission::whereIn('resource', [
            'User', 'Role', 'Permission', 'Setting', 'Log', 'Report'
        ])->get();
        $this->rbacService->syncRolePermissions($admin, $adminPermissions->pluck('id')->toArray());

        // 编辑权限
        $editor = Role::where('slug', 'editor')->first();
        $editorPermissions = Permission::whereIn('resource', [
            'Post', 'Comment', 'Category', 'Tag', 'File'
        ])->whereIn('action', ['view', 'create', 'update', 'delete'])->get();
        $this->rbacService->syncRolePermissions($editor, $editorPermissions->pluck('id')->toArray());

        // 作者权限
        $author = Role::where('slug', 'author')->first();
        $authorPermissions = Permission::whereIn('resource', ['Post', 'Category', 'Tag'])
            ->whereIn('action', ['view', 'create', 'update'])->get();
        $this->rbacService->syncRolePermissions($author, $authorPermissions->pluck('id')->toArray());

        // 审核员权限
        $reviewer = Role::where('slug', 'reviewer')->first();
        $reviewerPermissions = Permission::whereIn('resource', ['Post', 'Comment'])
            ->whereIn('action', ['view', 'update'])->get();
        $this->rbacService->syncRolePermissions($reviewer, $reviewerPermissions->pluck('id')->toArray());

        // 财务权限
        $finance = Role::where('slug', 'finance')->first();
        $financePermissions = Permission::whereIn('resource', ['Order', 'Report'])
            ->whereIn('action', ['view', 'export'])->get();
        $this->rbacService->syncRolePermissions($finance, $financePermissions->pluck('id')->toArray());

        // 普通用户权限
        $user = Role::where('slug', 'user')->first();
        $userPermissions = Permission::whereIn('resource', ['Post', 'Comment'])
            ->where('action', 'view')->get();
        $this->rbacService->syncRolePermissions($user, $userPermissions->pluck('id')->toArray());
    }

    /**
     * 分配数据范围给权限
     */
    protected function assignDataScopesToPermissions(): void
    {
        $this->command->info('分配数据范围给权限...');

        $allDataScope = DataScope::where('type', DataScopeType::ALL->value)->first();
        $orgDataScope = DataScope::where('type', DataScopeType::ORGANIZATION->value)->first();
        $deptDataScope = DataScope::where('type', DataScopeType::DEPARTMENT->value)->first();
        $personalDataScope = DataScope::where('type', DataScopeType::PERSONAL->value)->first();

        // 用户管理权限使用组织数据范围
        $userPermissions = Permission::where('resource', 'User')->get();
        foreach ($userPermissions as $permission) {
            $this->rbacService->assignDataScopeToPermission($permission, $orgDataScope);
        }

        // 文章权限使用个人数据范围
        $postPermissions = Permission::where('resource', 'Post')
            ->whereIn('action', ['create', 'update', 'delete'])->get();
        foreach ($postPermissions as $permission) {
            $this->rbacService->assignDataScopeToPermission($permission, $personalDataScope);
        }

        // 订单权限使用部门数据范围
        $orderPermissions = Permission::where('resource', 'Order')->get();
        foreach ($orderPermissions as $permission) {
            $this->rbacService->assignDataScopeToPermission($permission, $deptDataScope);
        }

        // 系统权限使用全部数据范围
        $systemPermissions = Permission::whereIn('resource', ['Setting', 'Log', 'System'])->get();
        foreach ($systemPermissions as $permission) {
            $this->rbacService->assignDataScopeToPermission($permission, $allDataScope);
        }
    }

    /**
     * 判断是否应该跳过某个权限
     */
    protected function shouldSkipPermission(string $resource, ActionType $action): bool
    {
        $skipRules = [
            'Log' => [ActionType::CREATE, ActionType::UPDATE, ActionType::IMPORT],
            'Setting' => [ActionType::IMPORT],
            'DataScope' => [ActionType::EXPORT, ActionType::IMPORT],
        ];

        return isset($skipRules[$resource]) && in_array($action, $skipRules[$resource]);
    }
}
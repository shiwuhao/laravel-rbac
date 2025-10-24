# Laravel RBAC 完整使用文档

> 现代化的 Laravel 12+ RBAC 扩展包，采用 Action 模式架构，提供完整的基于角色的访问控制（RBAC）和数据权限管理功能。

---

## 目录

- [快速开始](#快速开始)
- [核心概念](#核心概念)
- [使用场景](#使用场景)
- [功能详解](#功能详解)
  - [角色管理](#角色管理)
  - [权限管理](#权限管理)
  - [数据权限](#数据权限)
  - [用户授权](#用户授权)
- [Action 模式详解](#action-模式详解)
- [中间件使用](#中间件使用)
- [API 接口](#api-接口)
- [高级功能](#高级功能)
- [最佳实践](#最佳实践)
- [常见问题](#常见问题)

---

## 快速开始

### 1. 安装

```bash
composer require shiwuhao/laravel-rbac
```

### 2. 发布资源

```bash
# 发布所有资源
php artisan vendor:publish --provider="Rbac\RbacServiceProvider"

# 或分别发布
php artisan vendor:publish --tag=rbac-config      # 配置文件
php artisan vendor:publish --tag=rbac-migrations  # 迁移文件
php artisan vendor:publish --tag=rbac-routes      # 路由文件
```

### 3. 运行迁移

```bash
php artisan migrate
```

### 4. 配置用户模型

```php
// app/Models/User.php
use Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

### 5. 快速填充示例数据

```bash
php artisan rbac:quick-seed
```

---

## 核心概念

### RBAC 模型

本扩展包实现了完整的 RBAC（Role-Based Access Control）模型：

```
用户(User) -> 角色(Role) -> 权限(Permission) -> 数据范围(DataScope)
     |            |              |
     |------------|--------------|
          直接权限分配
```

### 核心组件

#### 1. **Role（角色）**
- 角色是权限的集合
- 用户通过角色获得权限
- 支持角色继承和层级结构

**示例：**
```php
// 创建管理员角色
$adminRole = Role::create([
    'name' => '系统管理员',
    'slug' => 'admin',
    'description' => '拥有系统所有权限',
    'guard_name' => 'web',
]);
```

#### 2. **Permission（权限）**
- 代表对资源的操作权限
- 支持通用权限和实例权限
- 可绑定数据范围

**示例：**
```php
// 创建用户管理权限
$permission = Permission::create([
    'name' => '查看用户',
    'slug' => 'users.view',
    'resource' => 'users',
    'action' => 'view',
    'description' => '查看用户列表和详情',
]);
```

#### 3. **DataScope（数据范围）**
- 控制数据级别的访问权限
- 支持多种范围类型（全部、组织、部门、个人、自定义）
- 可配置动态规则

**数据范围类型：**
```php
enum DataScopeType {
    ALL          // 全部数据
    ORGANIZATION // 组织数据
    DEPARTMENT   // 部门数据  
    PERSONAL     // 个人数据
    CUSTOM       // 自定义规则
}
```

### Action 模式

Action 是一个独立的业务逻辑单元，替代传统的控制器方法：

```php
// 传统方式
class RoleController {
    public function store(Request $request) {
        // 验证、创建逻辑
    }
}

// Action 方式
class CreateRole extends BaseAction {
    protected function rules() { /* 验证规则 */ }
    protected function execute() { /* 创建逻辑 */ }
}

// 路由直接绑定 Action
Route::post('/roles', CreateRole::class);
```

**优势：**
- ✅ 单一职责原则
- ✅ 高度可测试
- ✅ 跨场景复用（控制器、命令、队列）
- ✅ 完整的类型安全

---

## 使用场景

### 场景 1：企业后台管理系统

**需求：** 
- 不同部门的员工只能看到和管理自己部门的数据
- 管理员可以看到全部数据
- 需要细粒度的权限控制

**解决方案：**

```php
// 1. 创建角色
$deptManager = Role::create([
    'name' => '部门经理',
    'slug' => 'dept-manager',
]);

$admin = Role::create([
    'name' => '系统管理员', 
    'slug' => 'admin',
]);

// 2. 创建权限
$viewUsers = Permission::create([
    'name' => '查看用户',
    'slug' => 'users.view',
    'resource' => 'users',
    'action' => 'view',
]);

// 3. 创建数据范围
$deptScope = DataScope::create([
    'name' => '部门数据',
    'type' => DataScopeType::DEPARTMENT,
    'config' => ['field' => 'department_id'],
]);

$allScope = DataScope::create([
    'name' => '全部数据',
    'type' => DataScopeType::ALL,
]);

// 4. 关联权限和数据范围
$viewUsers->dataScopes()->attach($deptScope);
$viewUsers->dataScopes()->attach($allScope);

// 5. 分配权限给角色
$deptManager->permissions()->attach($viewUsers, [
    'data_scope_id' => $deptScope->id
]);

$admin->permissions()->attach($viewUsers, [
    'data_scope_id' => $allScope->id
]);

// 6. 给用户分配角色
$user1->assignRole($deptManager);  // 只能看部门数据
$user2->assignRole($admin);        // 可以看全部数据
```

### 场景 2：多租户 SaaS 平台

**需求：**
- 每个租户数据完全隔离
- 租户内部有不同角色和权限
- 需要支持租户级别的权限配置

**解决方案：**

```php
// 1. 创建租户隔离的数据范围
$tenantScope = DataScope::create([
    'name' => '租户数据隔离',
    'type' => DataScopeType::CUSTOM,
    'config' => [
        'field' => 'tenant_id',
        'operator' => '=',
        'value' => auth()->user()->tenant_id,
    ],
]);

// 2. 为所有权限绑定租户范围
Permission::all()->each(function ($permission) use ($tenantScope) {
    $permission->dataScopes()->syncWithoutDetaching($tenantScope);
});

// 3. 在查询时自动应用数据范围
User::query()
    ->applyDataScope($tenantScope, auth()->user())
    ->get();
```

### 场景 3：内容管理系统（CMS）

**需求：**
- 作者只能编辑自己的文章
- 编辑可以审核他人文章
- 管理员可以管理所有文章

**解决方案：**

```php
// 1. 创建角色
$author = Role::create(['name' => '作者', 'slug' => 'author']);
$editor = Role::create(['name' => '编辑', 'slug' => 'editor']);
$admin = Role::create(['name' => '管理员', 'slug' => 'admin']);

// 2. 创建权限
$editArticle = Permission::create([
    'slug' => 'articles.edit',
    'name' => '编辑文章',
    'resource' => 'articles',
    'action' => 'update',
]);

$approveArticle = Permission::create([
    'slug' => 'articles.approve',
    'name' => '审批文章',
    'resource' => 'articles',
    'action' => 'approve',
]);

// 3. 创建数据范围
$personalScope = DataScope::create([
    'name' => '个人文章',
    'type' => DataScopeType::PERSONAL,
    'config' => ['field' => 'author_id'],
]);

$allScope = DataScope::create([
    'name' => '全部文章',
    'type' => DataScopeType::ALL,
]);

// 4. 分配权限
$author->givePermission($editArticle);
$author->assignDataScope($personalScope);

$editor->givePermission([$editArticle, $approveArticle]);
$editor->assignDataScope($allScope);
```

### 场景 4：工单系统

**需求：**
- 客服只能处理分配给自己的工单
- 主管可以看到团队所有工单
- 支持动态权限分配

**解决方案：**

```php
// 使用实例权限
$ticket = Ticket::find(1);

// 分配工单给特定客服
$user->giveInstancePermission(
    'tickets',
    $ticket->id,
    'update'
);

// 检查权限
if ($user->hasInstancePermission('tickets', $ticket->id, 'update')) {
    // 允许处理
}

// 使用中间件保护路由
Route::put('/tickets/{id}', UpdateTicket::class)
    ->middleware('permission:tickets.update');
```

---

## 功能详解

### 角色管理

#### 创建角色

```php
use Rbac\Services\RbacService;

$rbacService = app(RbacService::class);

// 方式1：使用 Service
$role = $rbacService->createRole(
    name: '产品经理',
    slug: 'product-manager',
    description: '负责产品规划和管理',
    guard: 'web'
);

// 方式2：使用 Model
$role = Role::create([
    'name' => '产品经理',
    'slug' => 'product-manager',
    'description' => '负责产品规划和管理',
    'guard_name' => 'web',
]);

// 方式3：使用 Action
use Rbac\Actions\Role\CreateRole;

$role = CreateRole::handle([
    'name' => '产品经理',
    'slug' => 'product-manager',
    'description' => '负责产品规划和管理',
]);
```

#### 更新角色

```php
use Rbac\Actions\Role\UpdateRole;

$role = UpdateRole::handle([
    'name' => '高级产品经理',
    'description' => '负责核心产品规划',
], $roleId);
```

#### 删除角色

```php
use Rbac\Actions\Role\DeleteRole;

DeleteRole::handle([], $roleId);
```

#### 角色权限管理

```php
// 给角色分配权限
$role->givePermission(['users.view', 'users.create']);

// 撤销角色权限
$role->revokePermission('users.delete');

// 同步角色权限（覆盖）
$role->syncPermissions(['users.view', 'users.update']);

// 检查角色权限
if ($role->hasPermission('users.view')) {
    // 角色拥有该权限
}

// 检查角色是否有任一权限
if ($role->hasAnyPermission(['users.view', 'users.create'])) {
    // 角色拥有其中任一权限
}

// 检查角色是否拥有所有权限
if ($role->hasAllPermissions(['users.view', 'users.create'])) {
    // 角色拥有所有指定权限
}
```

### 权限管理

#### 创建权限

```php
use Rbac\Services\RbacService;
use Rbac\Enums\ActionType;

$rbacService = app(RbacService::class);

// 创建单个权限
$permission = $rbacService->createPermission(
    name: '创建订单',
    slug: 'orders.create',
    resource: 'orders',
    action: ActionType::CREATE,
    description: '允许创建新订单',
    guard: 'web',
    metadata: ['group' => 'orders']
);
```

#### 批量创建资源权限

```php
use Rbac\Actions\Permission\BatchCreatePermissions;

// 为"文章"资源创建所有CRUD权限
$permissions = BatchCreatePermissions::handle([
    'resource' => 'articles',
    'actions' => ['view', 'create', 'update', 'delete'],
    'guard_name' => 'web',
]);

// 返回：articles.view, articles.create, articles.update, articles.delete
```

#### 创建实例权限

```php
use Rbac\Actions\Permission\CreateInstancePermission;

// 为特定文章创建编辑权限
$permission = CreateInstancePermission::handle([
    'resource' => 'articles',
    'resource_id' => 123,
    'action' => 'update',
    'name' => '编辑文章#123',
]);
```

#### 权限查询

```php
// 获取所有权限
$allPermissions = Permission::all();

// 按资源查询
$articlePermissions = Permission::where('resource', 'articles')->get();

// 按slug查询
$permission = Permission::where('slug', 'users.view')->first();

// 使用 Service
$permission = $rbacService->getPermissionBySlug('users.view');
```

### 数据权限

#### 数据范围类型说明

| 类型 | 说明 | 使用场景 |
|------|------|----------|
| `ALL` | 全部数据 | 超级管理员、系统管理员 |
| `ORGANIZATION` | 组织数据 | 集团内多公司，只能看本公司 |
| `DEPARTMENT` | 部门数据 | 部门经理只能看本部门 |
| `PERSONAL` | 个人数据 | 普通员工只能看自己的 |
| `CUSTOM` | 自定义规则 | 复杂的数据权限逻辑 |

#### 创建数据范围

```php
use Rbac\Models\DataScope;
use Rbac\Enums\DataScopeType;

// 1. 全部数据范围
$allScope = DataScope::create([
    'name' => '全部数据',
    'type' => DataScopeType::ALL,
    'description' => '可访问所有数据',
]);

// 2. 部门数据范围
$deptScope = DataScope::create([
    'name' => '部门数据',
    'type' => DataScopeType::DEPARTMENT,
    'config' => [
        'field' => 'department_id',  // 数据表字段
        'operator' => '=',
        'value' => '{user.department_id}',  // 动态值
    ],
    'description' => '只能访问本部门数据',
]);

// 3. 自定义数据范围
$customScope = DataScope::create([
    'name' => '区域数据',
    'type' => DataScopeType::CUSTOM,
    'config' => [
        'field' => 'region_id',
        'operator' => 'in',
        'value' => '{user.managed_regions}',  // 用户管理的区域
    ],
]);
```

#### 应用数据范围

```php
// 1. 给用户分配数据范围
$user->assignDataScope($deptScope, 'department_id = ?');

// 2. 给权限绑定数据范围
$permission->dataScopes()->attach($deptScope, [
    'constraint' => 'department_id'
]);

// 3. 在查询中应用数据范围
$query = Article::query();

$user->getDataScopesForPermission('articles.view')
    ->each(function ($scope) use ($query, $user) {
        $scope->applyScope($query, $user);
    });

$articles = $query->get();  // 自动过滤数据
```

#### 数据范围实战示例

```php
// 场景：销售系统中的客户数据权限

// 1. 创建数据范围
$personalCustomers = DataScope::create([
    'name' => '我的客户',
    'type' => DataScopeType::PERSONAL,
    'config' => ['field' => 'sales_id'],
]);

$teamCustomers = DataScope::create([
    'name' => '团队客户',
    'type' => DataScopeType::DEPARTMENT,
    'config' => ['field' => 'team_id'],
]);

// 2. 创建权限并绑定数据范围
$viewCustomers = Permission::create([
    'slug' => 'customers.view',
    'name' => '查看客户',
    'resource' => 'customers',
    'action' => 'view',
]);

$viewCustomers->dataScopes()->attach([
    $personalCustomers->id,
    $teamCustomers->id,
]);

// 3. 给不同角色分配不同的数据范围
$salesRole = Role::create(['name' => '销售', 'slug' => 'sales']);
$salesRole->givePermission($viewCustomers);
$salesUser->assignRole($salesRole);
$salesUser->assignDataScope($personalCustomers);  // 只能看自己的客户

$managerRole = Role::create(['name' => '经理', 'slug' => 'manager']);
$managerRole->givePermission($viewCustomers);
$managerUser->assignRole($managerRole);
$managerUser->assignDataScope($teamCustomers);  // 可以看团队的客户
```

### 用户授权

#### 分配角色

```php
// 分配单个角色
$user->assignRole('admin');
$user->assignRole($adminRole);

// 分配多个角色
$user->assignRole(['admin', 'editor']);

// 移除角色
$user->removeRole('editor');

// 同步角色（覆盖现有角色）
$user->syncRoles(['admin']);
```

#### 直接分配权限

```php
// 给用户直接分配权限（不通过角色）
$user->givePermission('users.create');
$user->givePermission(['users.view', 'users.update']);

// 撤销直接权限
$user->revokePermission('users.create');

// 同步权限
$user->syncPermissions(['users.view', 'users.update']);
```

#### 检查权限

```php
// 检查角色
if ($user->hasRole('admin')) {
    // 用户拥有admin角色
}

if ($user->hasAnyRole(['admin', 'editor'])) {
    // 用户拥有admin或editor角色
}

if ($user->hasAllRoles(['admin', 'super-admin'])) {
    // 用户同时拥有两个角色
}

// 检查权限
if ($user->hasPermission('users.create')) {
    // 用户拥有创建用户权限
}

if ($user->hasAnyPermission(['users.view', 'users.create'])) {
    // 用户拥有任一权限
}

if ($user->hasAllPermissions(['users.view', 'users.create'])) {
    // 用户拥有所有权限
}

// 获取用户所有权限
$permissions = $user->getAllPermissions();

// 获取用户在特定权限下的数据范围
$scopes = $user->getDataScopesForPermission('users.view');
```

#### 实例权限

```php
// 检查用户对特定资源实例的权限
if ($user->hasInstancePermission('articles', 123, 'update')) {
    // 用户可以编辑文章#123
}

// 获取用户对特定资源实例的所有权限
$permissions = $user->getInstancePermissions('articles', 123);
// 返回：['view', 'update']

// 检查用户对资源类型的通用权限
if ($user->hasGeneralPermission('articles', 'create')) {
    // 用户可以创建任何文章
}
```

---

## Action 模式详解

### Action 基础

Action 是一个继承自 `BaseAction` 的类，包含三个核心部分：

1. **rules()** - 验证规则
2. **execute()** - 业务逻辑
3. **ActionContext** - 数据上下文

### 创建自定义 Action

```php
<?php

namespace App\Actions;

use Rbac\Actions\BaseAction;
use App\Models\Article;

class PublishArticle extends BaseAction
{
    /**
     * 定义验证规则
     */
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array',
        ];
    }

    /**
     * 执行业务逻辑
     */
    protected function execute(): Article
    {
        // 通过 context 访问数据
        $data = $this->context->all();
        
        // 获取当前用户
        $user = auth()->user();
        
        // 创建文章
        $article = Article::create([
            'title' => $this->context->data('title'),
            'content' => $this->context->data('content'),
            'category_id' => $this->context->data('category_id'),
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        // 关联标签
        if ($tags = $this->context->data('tags')) {
            $article->tags()->sync($tags);
        }
        
        return $article;
    }
}
```

### Action 调用方式

```php
// 1. 静态调用（推荐）
$article = PublishArticle::handle([
    'title' => '文章标题',
    'content' => '文章内容',
    'category_id' => 1,
    'tags' => [1, 2, 3],
]);

// 2. 实例调用（返回JSON响应）
$action = new PublishArticle();
$response = $action([
    'title' => '文章标题',
    'content' => '文章内容',
]);
// 返回 JsonResponse

// 3. 依赖注入调用
Route::post('/articles', PublishArticle::class);
```

### ActionContext API

```php
// 获取所有数据
$this->context->all();

// 获取单个字段（支持默认值）
$this->context->data('title', '默认标题');

// 获取额外参数（如路由参数）
$this->context->id();  // 第一个额外参数
$this->context->arg(1);  // 第二个额外参数

// 检查字段是否存在
$this->context->has('title');

// 获取指定字段
$this->context->only(['title', 'content']);
$this->context->except(['password']);
```

### 带权限注解的 Action

```php
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('article:*', '文章管理')]
#[Permission('article:publish', '发布文章')]
class PublishArticle extends BaseAction
{
    // Action 实现
}

// 扫描并自动生成权限
php artisan rbac:scan-permission-annotations
```

---

## 中间件使用

### 权限中间件

```php
// 单个权限
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:users.view');

// 多个权限（OR 逻辑）
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('permission:dashboard.view|reports.view');

// 多个权限（AND 逻辑）
Route::post('/users/export', [UserController::class, 'export'])
    ->middleware('permission:users.view&users.export');

// 复杂组合
Route::put('/articles/{id}', UpdateArticle::class)
    ->middleware('permission:(articles.update|articles.manage)&articles.publish');
```

### 角色中间件

```php
// 单个角色
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');

// 多个角色（OR 逻辑）
Route::get('/moderate', [ModerateController::class, 'index'])
    ->middleware('role:admin|moderator');

// 多个角色（AND 逻辑）
Route::delete('/users/{id}', DeleteUser::class)
    ->middleware('role:admin&super-admin');
```

### 路由组中使用

```php
Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});
```

### 数据范围中间件

```php
Route::middleware('data_scope:articles.view')->group(function () {
    Route::get('/articles', [ArticleController::class, 'index']);
});
```

---

## API 接口

本扩展包提供完整的 RESTful API，默认前缀为 `/api/rbac`。

### 角色接口

#### 获取角色列表

```http
GET /api/rbac/roles?keyword=admin&per_page=15&page=1

Response:
{
    "data": [
        {
            "id": 1,
            "name": "管理员",
            "slug": "admin",
            "description": "系统管理员",
            "created_at": "2024-01-01 00:00:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

#### 创建角色

```http
POST /api/rbac/roles
Content-Type: application/json

{
    "name": "产品经理",
    "slug": "product-manager",
    "description": "负责产品规划",
    "guard_name": "web"
}

Response:
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 2,
        "name": "产品经理",
        "slug": "product-manager"
    }
}
```

#### 更新角色

```http
PUT /api/rbac/roles/2
Content-Type: application/json

{
    "name": "高级产品经理",
    "description": "负责核心产品规划"
}
```

#### 删除角色

```http
DELETE /api/rbac/roles/2
```

#### 分配权限给角色

```http
POST /api/rbac/roles/1/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3],
    "replace": false
}
```

### 权限接口

#### 批量创建权限

```http
POST /api/rbac/permissions/batch
Content-Type: application/json

{
    "resource": "articles",
    "actions": ["view", "create", "update", "delete"],
    "guard_name": "web"
}

Response:
{
    "code": 200,
    "message": "批量创建成功",
    "data": [
        {"id": 1, "slug": "articles.view"},
        {"id": 2, "slug": "articles.create"},
        {"id": 3, "slug": "articles.update"},
        {"id": 4, "slug": "articles.delete"}
    ]
}
```

### 用户授权接口

#### 给用户分配角色

```http
POST /api/rbac/users/1/roles
Content-Type: application/json

{
    "role_id": 2
}
```

#### 批量分配角色

```http
POST /api/rbac/users/1/roles/batch
Content-Type: application/json

{
    "role_ids": [1, 2, 3],
    "replace": true
}
```

#### 获取用户权限

```http
GET /api/rbac/users/1/permissions

Response:
{
    "data": {
        "roles": [
            {"id": 1, "name": "管理员"}
        ],
        "permissions": [
            {"id": 1, "slug": "users.view"},
            {"id": 2, "slug": "users.create"}
        ]
    }
}
```

---

## 高级功能

### 权限缓存

系统自动缓存用户权限，提升性能：

```php
// 缓存配置（config/rbac.php）
'cache' => [
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    'key' => 'laravel_rbac.cache',
],

// 手动清除用户权限缓存
$user->forgetCachedPermissions();

// 清除所有RBAC缓存
php artisan rbac:clear-cache
```

### 超级管理员

配置超级管理员角色，自动拥有所有权限：

```php
// config/rbac.php
'super_admin_role' => 'super-admin',

// 检查是否为超级管理员
if ($user->isSuperAdmin()) {
    // 拥有所有权限
}
```

### 权限 Gates

系统自动注册所有权限到 Laravel Gates：

```php
// 使用 Gate 检查权限
if (Gate::allows('users.create')) {
    // 允许创建用户
}

// 在控制器中使用
$this->authorize('users.update', $user);

// 在 Blade 中使用
@can('users.delete')
    <button>删除用户</button>
@endcan
```

### 自动路由权限生成

```bash
# 扫描所有路由并生成对应权限
php artisan rbac:sync-permissions-from-routes

# 配置自动生成（config/rbac.php）
'route_permission' => [
    'auto_generate' => true,
    'skip_patterns' => [
        'debugbar.*',
        'telescope.*',
    ],
],
```

### Blade 指令

```blade
{{-- 检查权限 --}}
@permission('users.create')
    <button>创建用户</button>
@endpermission

@anypermission('users.create', 'users.update')
    <button>编辑</button>
@endanypermission

{{-- 检查角色 --}}
@role('admin')
    <a href="/admin">管理后台</a>
@endrole

@anyrole('admin', 'moderator')
    <a href="/moderate">内容审核</a>
@endanyrole

{{-- 检查多个权限 --}}
@allpermissions('users.view', 'users.create')
    <button>高级操作</button>
@endallpermissions
```

---

## 最佳实践

### 1. 权限命名规范

```php
// 推荐格式：resource.action
'users.view'       // 查看用户
'users.create'     // 创建用户
'users.update'     // 更新用户
'users.delete'     // 删除用户
'users.export'     // 导出用户

// 分组管理
'admin.users.manage'      // 管理员：用户管理
'admin.settings.view'     // 管理员：查看设置
```

### 2. 角色设计原则

```php
// ✅ 好的设计
$admin = Role::create(['slug' => 'admin', 'name' => '系统管理员']);
$editor = Role::create(['slug' => 'editor', 'name' => '内容编辑']);
$viewer = Role::create(['slug' => 'viewer', 'name' => '访客']);

// ❌ 避免的设计
$userWithPermission123 = Role::create(['slug' => 'user-perm-123']);
```

### 3. 数据范围最佳实践

```php
// 1. 为不同级别创建清晰的数据范围
$scopes = [
    'all' => DataScopeType::ALL,           // 全部数据
    'org' => DataScopeType::ORGANIZATION,  // 组织数据
    'dept' => DataScopeType::DEPARTMENT,   // 部门数据
    'personal' => DataScopeType::PERSONAL, // 个人数据
];

// 2. 在查询时统一应用数据范围
class ArticleRepository {
    public function getUserArticles(User $user) {
        $query = Article::query();
        
        // 应用用户的数据范围
        $user->getDataScopesForPermission('articles.view')
            ->each(fn($scope) => $scope->applyScope($query, $user));
            
        return $query->get();
    }
}
```

### 4. 性能优化

```php
// 1. 预加载关联关系
$users = User::with(['roles.permissions', 'directPermissions'])->get();

// 2. 使用缓存
$permissions = Cache::remember(
    "user.{$userId}.permissions",
    now()->addDay(),
    fn() => $user->getAllPermissions()
);

// 3. 批量检查权限
$hasPermissions = $user->hasAllPermissions([
    'users.view',
    'users.create',
    'users.update',
]);
```

### 5. 测试权限

```php
// tests/Feature/PermissionTest.php
public function test_admin_can_create_users()
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $this->actingAs($admin)
        ->post('/api/users', ['name' => 'Test User'])
        ->assertStatus(201);
}

public function test_regular_user_cannot_create_users()
{
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post('/api/users', ['name' => 'Test User'])
        ->assertStatus(403);
}
```

---

## 常见问题

### Q: 如何实现多租户隔离？

```php
// 1. 创建租户数据范围
$tenantScope = DataScope::create([
    'type' => DataScopeType::CUSTOM,
    'config' => [
        'field' => 'tenant_id',
        'value' => auth()->user()->tenant_id,
    ],
]);

// 2. 全局应用
Model::addGlobalScope('tenant', function ($query) {
    $query->where('tenant_id', auth()->user()->tenant_id);
});
```

### Q: 权限检查性能如何优化？

```php
// 1. 启用权限缓存
config(['rbac.cache.expiration_time' => DateInterval::createFromDateString('24 hours')]);

// 2. 预加载关联
$user->load(['roles.permissions', 'directPermissions']);

// 3. 使用数据库索引
// 在迁移文件中添加索引
$table->index(['resource', 'action']);
```

### Q: 如何处理权限变更后的缓存？

```php
// 监听权限变更事件
Event::listen(PermissionChanged::class, function ($event) {
    // 清除相关用户的缓存
    $event->users->each->forgetCachedPermissions();
});

// 或使用观察者
// app/Observers/PermissionObserver.php
public function updated(Permission $permission)
{
    $permission->users->each->forgetCachedPermissions();
}
```

### Q: 如何实现动态权限？

```php
// 使用 Gate 定义动态权限
Gate::define('edit-post', function ($user, $post) {
    return $user->id === $post->author_id
        || $user->hasPermission('posts.update');
});

// 检查
if (Gate::allows('edit-post', $post)) {
    // 允许编辑
}
```

---

## Artisan 命令参考

```bash
# 安装 RBAC
php artisan rbac:install

# 创建角色
php artisan rbac:create-role {slug} {name} {description?}

# 创建权限
php artisan rbac:create-permission {slug} {name} {description?}

# 扫描路由生成权限
php artisan rbac:sync-permissions-from-routes

# 扫描注解生成权限
php artisan rbac:scan-permission-annotations

# 快速填充示例数据
php artisan rbac:quick-seed

# 填充完整测试数据
php artisan rbac:seed-test-data

# 查看 RBAC 状态
php artisan rbac:status

# 查看权限列表
php artisan rbac:list-permissions

# 清除 RBAC 缓存
php artisan rbac:clear-cache
```

---

## 更多资源

- [GitHub 仓库](https://github.com/shiwuhao/laravel-rbac)
- [命令文档](./COMMANDS.md)
- [更新日志](../CHANGELOG.md)
- [贡献指南](../CONTRIBUTING.md)

---

**有问题或建议？** 请提交 [Issue](https://github.com/shiwuhao/laravel-rbac/issues) 或发送邮件到 [admin@shiwuhao.com](mailto:admin@shiwuhao.com)

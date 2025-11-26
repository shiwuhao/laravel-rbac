# Laravel RBAC 完整使用文档

> 现代化的 Laravel 12+ RBAC 扩展包，采用 Action 模式架构，提供完整的基于角色的访问控制（RBAC）和数据权限管理功能。

---

## 目录

- [快速开始](#快速开始)
- [核心概念](#核心概念)
- [角色管理](#角色管理)
- [权限管理](#权限管理)
- [数据范围（Data Scope）](#数据范围data-scope)
- [实例权限](#实例权限)
- [用户授权](#用户授权)
- [中间件使用](#中间件使用)
- [API 接口](#api-接口)
- [Action 模式详解](#action-模式详解)
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

在你的 User 模型中添加 `HasRolesAndPermissions` Trait：

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
    
    // ...
}
```

### 5. 快速填充示例数据（可选）

```bash
php artisan rbac:quick-seed
```

---

## 核心概念

### RBAC 模型架构

本扩展包实现了完整的 RBAC（Role-Based Access Control）模型：

```
用户(User) ←→ 角色(Role) ←→ 权限(Permission) ←→ 数据范围(DataScope)
     ↑             ↑              ↑
     └─────────────┴──────────────┘
          直接权限分配（跳过角色）
```

**核心特性：**
- ✅ 用户可以拥有多个角色
- ✅ 角色可以拥有多个权限
- ✅ 用户可以直接拥有权限（无需通过角色）
- ✅ 权限可以绑定数据范围（控制数据访问边界）
- ✅ 支持实例级别权限（针对特定资源实例）

---

### 核心组件

#### 1. **Role（角色）**

角色是权限的集合，用于简化权限管理。

**数据库字段：**
- `id` - 主键
- `name` - 角色名称（如：系统管理员）
- `slug` - 角色标识符（如：admin）
- `description` - 角色描述
- `guard_name` - 守卫名称（web/api）

**模型关系：**
```php
// 角色的权限
$role->permissions();

// 角色的用户
$role->users();

// 角色的数据范围
$role->dataScopes();
```

**示例：**
```php
use Rbac\Actions\Role\CreateRole;

$role = CreateRole::handle([
    'name' => '系统管理员',
    'slug' => 'admin',
    'description' => '拥有系统所有权限',
    'guard_name' => 'web',
]);
```

---

#### 2. **Permission（权限）**

权限代表对资源的操作权限，分为**通用权限**和**实例权限**。

**数据库字段：**
- `id` - 主键
- `name` - 权限名称（如：查看用户）
- `slug` - 权限标识符（如：user:view）
- `resource` - 资源类型（如：user）
- `action` - 操作类型（如：view）
- `resource_type` - 实例权限的模型类（可空）
- `resource_id` - 实例权限的模型ID（可空）
- `description` - 权限描述
- `guard_name` - 守卫名称

**权限类型：**

**通用权限：**
- `resource_type` 和 `resource_id` 为 `null`
- 代表对某类资源的操作权限
- 示例：`user:view` - 查看所有用户

**实例权限：**
- `resource_type` 和 `resource_id` 有值
- 代表对特定资源实例的操作权限
- 示例：`article:update` + `resource_id=123` - 只能编辑文章#123

**模型关系：**
```php
// 权限的角色
$permission->roles();

// 权限的用户（直接分配）
$permission->users();

// 权限的数据范围
$permission->dataScopes();
```

---

#### 3. **DataScope（数据范围）**

数据范围控制用户在拥有权限时，能够访问的数据边界。

**数据库字段：**
- `id` - 主键
- `name` - 数据范围名称
- `type` - 数据范围类型（枚举）
- `config` - 配置数据（JSON）
- `description` - 描述

**数据范围类型：**

| 类型 | 说明 | 使用场景 | 配置示例 |
|------|------|----------|----------|
| `ALL` | 全部数据 | 超级管理员 | `null` |
| `ORGANIZATION` | 组织数据 | 集团多公司 | `{"field": "company_id"}` |
| `DEPARTMENT` | 部门数据 | 部门经理 | `{"field": "department_id"}` |
| `PERSONAL` | 个人数据 | 普通员工 | `{"field": "user_id"}` |
| `CUSTOM` | 自定义规则 | 复杂场景 | `{"field": "region_id", "operator": "in"}` |

**数据范围生效规则：**

```
有效数据范围 = 权限级数据范围 ∩ (用户级数据范围 ∪ 角色级数据范围)
```

即：
1. 权限定义的数据范围是**硬边界**（不能超出）
2. 用户/角色的数据范围在权限边界内进一步限制
3. 多个数据范围求**交集**（更严格）或**并集**（更宽松，可配置）

---

## 角色管理

### 创建角色

**使用 Action：**
```php
use Rbac\Actions\Role\CreateRole;

$role = CreateRole::handle([
    'name' => '产品经理',
    'slug' => 'product-manager',
    'description' => '负责产品规划和管理',
    'guard_name' => 'web',
]);
```

**使用 API：**
```http
POST /api/rbac/roles
Content-Type: application/json

{
    "name": "产品经理",
    "slug": "product-manager",
    "description": "负责产品规划和管理",
    "guard_name": "web"
}
```

---

### 更新角色

**使用 Action：**
```php
use Rbac\Actions\Role\UpdateRole;

$role = UpdateRole::handle([
    'name' => '高级产品经理',
    'description' => '负责核心产品规划',
], $roleId);
```

**使用 API：**
```http
PUT /api/rbac/roles/{id}
Content-Type: application/json

{
    "name": "高级产品经理",
    "description": "负责核心产品规划"
}
```

---

### 删除角色

**使用 Action：**
```php
use Rbac\Actions\Role\DeleteRole;

DeleteRole::handle([], $roleId);
```

**使用 API：**
```http
DELETE /api/rbac/roles/{id}
```

---

### 为角色分配权限

#### 分配权限（累加）

**使用 Action：**
```php
use Rbac\Actions\Role\AssignPermissionsToRole;

AssignPermissionsToRole::handle([
    'permissions' => [
        ['permission_id' => 1],
        ['permission_id' => 2],
        ['permission_id' => 3],
    ],
], $roleId);

// 或简化写法
AssignPermissionsToRole::handle([
    'permission_ids' => [1, 2, 3],
], $roleId);
```

**使用 API：**
```http
POST /api/rbac/roles/{id}/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3]
}
```

---

#### 撤销权限（批量）

**使用 Action：**
```php
use Rbac\Actions\Role\RevokePermissionsFromRole;

RevokePermissionsFromRole::handle([
    'permission_ids' => [1, 2, 3],
], $roleId);
```

**使用 API：**
```http
DELETE /api/rbac/roles/{id}/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3]
}
```

---

#### 同步权限（替换）

**使用 Action：**
```php
use Rbac\Actions\Role\SyncPermissionsToRole;

SyncPermissionsToRole::handle([
    'permission_ids' => [1, 2, 3],
], $roleId);
```

**使用 API：**
```http
PUT /api/rbac/roles/{id}/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3]
}
```

---

### 为角色分配数据范围

#### 分配数据范围（累加）

**使用 Action：**
```php
use Rbac\Actions\Role\AssignDataScopesToRole;

AssignDataScopesToRole::handle([
    'data_scopes' => [
        ['data_scope_id' => 1, 'constraint' => 'department_id = 10'],
        ['data_scope_id' => 2],
    ],
], $roleId);
```

**使用 API：**
```http
POST /api/rbac/roles/{id}/data-scopes
Content-Type: application/json

{
    "data_scopes": [
        {"data_scope_id": 1, "constraint": "department_id = 10"},
        {"data_scope_id": 2}
    ]
}
```

---

#### 撤销数据范围（批量）

**使用 Action：**
```php
use Rbac\Actions\Role\RevokeDataScopesFromRole;

RevokeDataScopesFromRole::handle([
    'data_scope_ids' => [1, 2, 3],
], $roleId);
```

**使用 API：**
```http
DELETE /api/rbac/roles/{id}/data-scopes
Content-Type: application/json

{
    "data_scope_ids": [1, 2, 3]
}
```

---

#### 同步数据范围（替换）

**使用 Action：**
```php
use Rbac\Actions\Role\SyncDataScopesToRole;

SyncDataScopesToRole::handle([
    'data_scopes' => [
        ['data_scope_id' => 1],
        ['data_scope_id' => 2],
    ],
], $roleId);
```

**使用 API：**
```http
PUT /api/rbac/roles/{id}/data-scopes
Content-Type: application/json

{
    "data_scopes": [
        {"data_scope_id": 1},
        {"data_scope_id": 2}
    ]
}
```

---

## 权限管理

### 创建通用权限

**使用 Action：**
```php
use Rbac\Actions\Permission\CreatePermission;

$permission = CreatePermission::handle([
    'name' => '查看用户',
    'slug' => 'user:view',
    'resource' => 'user',
    'action' => 'view',
    'description' => '查看用户列表和详情',
    'guard_name' => 'web',
]);
```

**使用 API：**
```http
POST /api/rbac/permissions
Content-Type: application/json

{
    "name": "查看用户",
    "slug": "user:view",
    "resource": "user",
    "action": "view",
    "description": "查看用户列表和详情"
}
```

---

### 批量创建权限

**使用 Action：**
```php
use Rbac\Actions\Permission\BatchCreatePermissions;

$permissions = BatchCreatePermissions::handle([
    'resource' => 'article',
    'actions' => ['view', 'create', 'update', 'delete'],
    'guard_name' => 'web',
]);

// 创建：article:view, article:create, article:update, article:delete
```

**使用 API：**
```http
POST /api/rbac/permissions/batch
Content-Type: application/json

{
    "resource": "article",
    "actions": ["view", "create", "update", "delete"]
}
```

---

### 创建实例权限

实例权限针对特定的资源实例，按需创建。

**使用 Action：**
```php
use Rbac\Actions\Permission\CreateInstancePermission;

$permission = CreateInstancePermission::handle([
    'resource' => 'article',
    'resource_id' => 123,
    'action' => 'update',
    'name' => '编辑文章#123',
]);
```

**使用 API：**
```http
POST /api/rbac/permissions/instance
Content-Type: application/json

{
    "resource": "article",
    "resource_id": 123,
    "action": "update",
    "name": "编辑文章#123"
}
```

---

### 使用权限注解自动生成

在 Action 类上使用 `#[Permission]` 注解：

```php
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('article:*', '文章管理')]
#[Permission('article:create', '创建文章', description: '允许创建新文章')]
class CreateArticle extends BaseAction
{
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'content' => 'required|string',
        ];
    }

    protected function execute()
    {
        return Article::create($this->context->all());
    }
}
```

然后运行扫描命令：

```bash
# 预览
php artisan rbac:scan-permissions --dry-run

# 执行生成
php artisan rbac:scan-permissions

# 强制覆盖已存在的权限
php artisan rbac:scan-permissions --force
```

---

## 数据范围（Data Scope）

数据范围用于控制用户在拥有权限时，能够访问的数据边界。

### 创建数据范围

#### 1. 全部数据范围

```php
use Rbac\Actions\DataScope\CreateDataScope;
use Rbac\Enums\DataScopeType;

$allScope = CreateDataScope::handle([
    'name' => '全部数据',
    'type' => DataScopeType::ALL,
    'description' => '可访问所有数据',
]);
```

---

#### 2. 部门数据范围

```php
$deptScope = CreateDataScope::handle([
    'name' => '部门数据',
    'type' => DataScopeType::DEPARTMENT,
    'config' => [
        'field' => 'department_id',
        'operator' => '=',
        'value' => '{user.department_id}',  // 动态取值
    ],
    'description' => '只能访问本部门数据',
]);
```

---

#### 3. 个人数据范围

```php
$personalScope = CreateDataScope::handle([
    'name' => '个人数据',
    'type' => DataScopeType::PERSONAL,
    'config' => [
        'field' => 'user_id',
        'operator' => '=',
        'value' => '{user.id}',
    ],
    'description' => '只能访问个人数据',
]);
```

---

#### 4. 自定义数据范围

```php
$customScope = CreateDataScope::handle([
    'name' => '区域数据',
    'type' => DataScopeType::CUSTOM,
    'config' => [
        'field' => 'region_id',
        'operator' => 'in',
        'value' => '{user.managed_regions}',  // 用户管理的区域列表
    ],
    'description' => '只能访问管辖区域数据',
]);
```

---

### 为权限绑定数据范围

权限绑定数据范围后，拥有该权限的用户只能访问范围内的数据。

```php
use Rbac\Models\Permission;

$permission = Permission::where('slug', 'article:view')->first();

// 绑定数据范围
$permission->dataScopes()->attach([
    $deptScope->id => ['constraint' => null],
    $personalScope->id => ['constraint' => null],
]);

// 或使用 sync（替换）
$permission->dataScopes()->sync([
    $deptScope->id,
    $personalScope->id,
]);
```

---

### 在模型中应用数据范围

#### 方式一：使用 Trait（推荐）

在需要数据范围控制的模型中使用 `HasDataScopeScope` Trait：

```php
use Illuminate\Database\Eloquent\Model;
use Rbac\Traits\HasDataScopeScope;

class Article extends Model
{
    use HasDataScopeScope;
    
    // ...
}
```

当查询时，数据范围会自动应用：

```php
// 自动应用当前用户的数据范围
$articles = Article::all();

// 临时禁用数据范围
$allArticles = Article::withoutDataScope()->get();
```

---

#### 方式二：手动应用

```php
use Rbac\Scopes\DataScopeGlobal;

$user = auth()->user();

// 获取用户对特定权限的数据范围
$scopes = $user->getDataScopesForPermission('article:view');

$query = Article::query();

// 应用数据范围
foreach ($scopes as $scope) {
    $scope->applyScope($query, $user);
}

$articles = $query->get();
```

---

### 数据范围配置

在 `config/rbac.php` 中配置数据范围行为：

```php
'data_scope' => [
    // 无有效范围时策略
    // deny: 返回空结果（默认，更安全）
    // ignore: 不应用范围（查询所有数据）
    'empty_strategy' => 'deny',

    // 组合模式
    // and: 交集（多个范围取交集，更严格）
    // or: 并集（多个范围取并集，更宽松）
    'mode' => 'and',
],
```

---

### 数据范围实战示例

#### 示例1：销售系统客户数据权限

```php
use Rbac\Actions\DataScope\CreateDataScope;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Actions\Role\CreateRole;
use Rbac\Actions\Role\AssignPermissionsToRole;

// 1. 创建数据范围
$personalCustomers = CreateDataScope::handle([
    'name' => '我的客户',
    'type' => DataScopeType::PERSONAL,
    'config' => ['field' => 'sales_id'],
]);

$teamCustomers = CreateDataScope::handle([
    'name' => '团队客户',
    'type' => DataScopeType::DEPARTMENT,
    'config' => ['field' => 'team_id'],
]);

// 2. 创建权限并绑定数据范围
$viewCustomers = CreatePermission::handle([
    'slug' => 'customer:view',
    'name' => '查看客户',
    'resource' => 'customer',
    'action' => 'view',
]);

$viewCustomers->dataScopes()->attach([
    $personalCustomers->id,
    $teamCustomers->id,
]);

// 3. 创建角色并分配权限
$salesRole = CreateRole::handle(['name' => '销售', 'slug' => 'sales']);
$managerRole = CreateRole::handle(['name' => '经理', 'slug' => 'manager']);

AssignPermissionsToRole::handle(['permission_ids' => [$viewCustomers->id]], $salesRole->id);
AssignPermissionsToRole::handle(['permission_ids' => [$viewCustomers->id]], $managerRole->id);

// 4. 为不同角色分配不同的数据范围
$salesRole->dataScopes()->attach($personalCustomers->id);  // 销售只能看自己的
$managerRole->dataScopes()->attach($teamCustomers->id);    // 经理可以看团队的

// 5. 用户查询时自动应用数据范围
$salesUser = User::find(1);
$salesUser->assignRole($salesRole);

// 在 Customer 模型中使用 HasDataScopeScope Trait
$customers = Customer::all();  // 自动只返回该销售的客户
```

---

## 实例权限

实例权限针对特定的资源实例，用于细粒度的权限控制。

### 为用户分配实例权限

#### 单个实例权限

**使用 Action：**
```php
use Rbac\Actions\User\AssignInstancePermissionToUser;

AssignInstancePermissionToUser::handle([
    'resource' => 'article',
    'resource_id' => 123,
    'action' => 'update',
], $userId);
```

**使用 API：**
```http
POST /api/rbac/users/{user_id}/instance-permissions
Content-Type: application/json

{
    "resource": "article",
    "resource_id": 123,
    "action": "update"
}
```

---

#### 批量实例权限

**使用 Action：**
```php
AssignInstancePermissionToUser::handle([
    'permissions' => [
        ['resource' => 'article', 'resource_id' => 123, 'action' => 'update'],
        ['resource' => 'article', 'resource_id' => 124, 'action' => 'update'],
        ['resource' => 'article', 'resource_id' => 125, 'action' => 'delete'],
    ],
], $userId);
```

**使用 API：**
```http
POST /api/rbac/users/{user_id}/instance-permissions
Content-Type: application/json

{
    "permissions": [
        {"resource": "article", "resource_id": 123, "action": "update"},
        {"resource": "article", "resource_id": 124, "action": "update"},
        {"resource": "article", "resource_id": 125, "action": "delete"}
    ]
}
```

---

### 为角色分配实例权限

角色也可以拥有实例权限，角色成员自动继承。

**使用 Action：**
```php
use Rbac\Actions\Role\AssignInstancePermissionToRole;

AssignInstancePermissionToRole::handle([
    'permissions' => [
        ['resource' => 'project', 'resource_id' => 10, 'action' => 'manage'],
    ],
], $roleId);
```

**使用 API：**
```http
POST /api/rbac/roles/{id}/instance-permissions
Content-Type: application/json

{
    "permissions": [
        {"resource": "project", "resource_id": 10, "action": "manage"}
    ]
}
```

---

### 撤销实例权限

#### 撤销用户实例权限（批量）

**使用 Action：**
```php
use Rbac\Actions\User\RevokeInstancePermissionsFromUser;

RevokeInstancePermissionsFromUser::handle([
    'permissions' => [
        ['resource' => 'article', 'resource_id' => 123, 'action' => 'update'],
        ['resource' => 'article', 'resource_id' => 124, 'action' => 'update'],
    ],
], $userId);
```

**使用 API：**
```http
DELETE /api/rbac/users/{user_id}/instance-permissions
Content-Type: application/json

{
    "permissions": [
        {"resource": "article", "resource_id": 123, "action": "update"},
        {"resource": "article", "resource_id": 124, "action": "update"}
    ]
}
```

---

#### 撤销角色实例权限（批量）

**使用 Action：**
```php
use Rbac\Actions\Role\RevokeInstancePermissionsFromRole;

RevokeInstancePermissionsFromRole::handle([
    'permissions' => [
        ['resource' => 'project', 'resource_id' => 10, 'action' => 'manage'],
        ['resource' => 'project', 'resource_id' => 11, 'action' => 'manage'],
    ],
], $roleId);
```

**使用 API：**
```http
DELETE /api/rbac/roles/{id}/instance-permissions
Content-Type: application/json

{
    "permissions": [
        {"resource": "project", "resource_id": 10, "action": "manage"},
        {"resource": "project", "resource_id": 11, "action": "manage"}
    ]
}
```

---

### 检查实例权限

```php
$user = auth()->user();

// 检查用户是否有特定实例的权限
if ($user->hasInstancePermission('article', 123, 'update')) {
    // 用户可以编辑文章#123
}

// 获取用户对特定实例的所有权限
$permissions = $user->getInstancePermissions('article', 123);
// 返回：['view', 'update']
```

---

### 实例权限使用场景

#### 1. 文档协作系统

```php
// 将文档分配给特定用户编辑
$user->giveInstancePermission('document', $documentId, 'edit');

// 检查权限
if ($user->hasInstancePermission('document', $documentId, 'edit')) {
    // 允许编辑
}
```

---

#### 2. 项目管理系统

```php
// 将项目管理权限分配给项目经理
$projectManager->giveInstancePermission('project', $projectId, 'manage');

// 检查权限
if ($user->hasInstancePermission('project', $projectId, 'manage')) {
    // 允许管理项目
}
```

---

#### 3. 工单系统

```php
// 将工单分配给客服
$customerService->giveInstancePermission('ticket', $ticketId, 'handle');

// 检查权限
if ($user->hasInstancePermission('ticket', $ticketId, 'handle')) {
    // 允许处理工单
}
```

---

## 用户授权

### 分配角色给用户

#### 分配角色（累加）

**使用 Action：**
```php
use Rbac\Actions\User\AssignRolesToUser;

AssignRolesToUser::handle([
    'roles' => [
        ['role_id' => 1],
        ['role_id' => 2],
    ],
], $userId);

// 或简化写法
AssignRolesToUser::handle([
    'role_ids' => [1, 2],
], $userId);
```

**使用 API：**
```http
POST /api/rbac/users/{user_id}/roles
Content-Type: application/json

{
    "role_ids": [1, 2]
}
```

---

#### 撤销角色（批量）

**使用 Action：**
```php
use Rbac\Actions\User\RevokeRolesFromUser;

RevokeRolesFromUser::handle([
    'role_ids' => [1, 2],
], $userId);
```

**使用 API：**
```http
DELETE /api/rbac/users/{user_id}/roles
Content-Type: application/json

{
    "role_ids": [1, 2]
}
```

---

#### 同步角色（替换）

**使用 Action：**
```php
use Rbac\Actions\User\SyncRolesToUser;

SyncRolesToUser::handle([
    'role_ids' => [1, 2],
], $userId);
```

**使用 API：**
```http
PUT /api/rbac/users/{user_id}/roles
Content-Type: application/json

{
    "role_ids": [1, 2]
}
```

---

### 直接分配权限给用户

用户可以直接拥有权限，无需通过角色。

#### 分配权限（累加）

**使用 Action：**
```php
use Rbac\Actions\User\AssignPermissionsToUser;

AssignPermissionsToUser::handle([
    'permission_ids' => [1, 2, 3],
], $userId);
```

**使用 API：**
```http
POST /api/rbac/users/{user_id}/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3]
}
```

---

#### 撤销权限（批量）

**使用 Action：**
```php
use Rbac\Actions\User\RevokePermissionsFromUser;

RevokePermissionsFromUser::handle([
    'permission_ids' => [1, 2, 3],
], $userId);
```

**使用 API：**
```http
DELETE /api/rbac/users/{user_id}/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3]
}
```

---

#### 同步权限（替换）

**使用 Action：**
```php
use Rbac\Actions\User\SyncPermissionsToUser;

SyncPermissionsToUser::handle([
    'permission_ids' => [1, 2, 3],
], $userId);
```

**使用 API：**
```http
PUT /api/rbac/users/{user_id}/permissions
Content-Type: application/json

{
    "permission_ids": [1, 2, 3]
}
```

---

### 为用户分配数据范围

#### 分配数据范围（累加）

**使用 Action：**
```php
use Rbac\Actions\User\AssignDataScopesToUser;

AssignDataScopesToUser::handle([
    'data_scopes' => [
        ['data_scope_id' => 1, 'constraint' => 'department_id = 10'],
        ['data_scope_id' => 2],
    ],
], $userId);
```

**使用 API：**
```http
POST /api/rbac/users/{user_id}/data-scopes
Content-Type: application/json

{
    "data_scopes": [
        {"data_scope_id": 1, "constraint": "department_id = 10"},
        {"data_scope_id": 2}
    ]
}
```

---

#### 撤销数据范围（批量）

**使用 Action：**
```php
use Rbac\Actions\User\RevokeDataScopesFromUser;

RevokeDataScopesFromUser::handle([
    'data_scope_ids' => [1, 2, 3],
], $userId);
```

**使用 API：**
```http
DELETE /api/rbac/users/{user_id}/data-scopes
Content-Type: application/json

{
    "data_scope_ids": [1, 2, 3]
}
```

---

#### 同步数据范围（替换）

**使用 Action：**
```php
use Rbac\Actions\User\SyncDataScopesToUser;

SyncDataScopesToUser::handle([
    'data_scopes' => [
        ['data_scope_id' => 1],
        ['data_scope_id' => 2],
    ],
], $userId);
```

**使用 API：**
```http
PUT /api/rbac/users/{user_id}/data-scopes
Content-Type: application/json

{
    "data_scopes": [
        {"data_scope_id": 1},
        {"data_scope_id": 2}
    ]
}
```

---

### 检查用户权限

```php
$user = auth()->user();

// 检查角色
if ($user->hasRole('admin')) {
    // 用户拥有 admin 角色
}

if ($user->hasAnyRole(['admin', 'editor'])) {
    // 用户拥有 admin 或 editor 角色
}

if ($user->hasAllRoles(['admin', 'super-admin'])) {
    // 用户同时拥有两个角色
}

// 检查权限
if ($user->hasPermission('user:create')) {
    // 用户拥有创建用户权限
}

if ($user->hasAnyPermission(['user:view', 'user:create'])) {
    // 用户拥有任一权限
}

if ($user->hasAllPermissions(['user:view', 'user:create'])) {
    // 用户拥有所有权限
}

// 获取用户所有权限
$permissions = $user->getAllPermissions();

// 获取用户在特定权限下的数据范围
$scopes = $user->getDataScopesForPermission('user:view');
```

---

## 中间件使用

### 权限中间件

#### 单个权限

```php
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:user:view');
```

---

#### 多个权限（OR 逻辑）

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('permission:dashboard:view|report:view');
```

---

#### 多个权限（AND 逻辑）

```php
Route::post('/users/export', [UserController::class, 'export'])
    ->middleware('permission:user:view&user:export');
```

---

#### 复杂组合

```php
Route::put('/articles/{id}', UpdateArticle::class)
    ->middleware('permission:(article:update|article:manage)&article:publish');
```

---

### 角色中间件

#### 单个角色

```php
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');
```

---

#### 多个角色（OR 逻辑）

```php
Route::get('/moderate', [ModerateController::class, 'index'])
    ->middleware('role:admin|moderator');
```

---

#### 多个角色（AND 逻辑）

```php
Route::delete('/users/{id}', DeleteUser::class)
    ->middleware('role:admin&super-admin');
```

---

### 路由组中使用

```php
// 权限保护的路由组
Route::middleware(['auth', 'permission:user:view'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
});

// 角色保护的路由组
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});
```

---

### 数据范围中间件

```php
Route::middleware('data_scope:article:view')->group(function () {
    Route::get('/articles', [ArticleController::class, 'index']);
});
```

---

### 在 Action 中使用权限注解

使用 `#[Permission]` 注解，路由会自动应用权限检查：

```php
use Rbac\Attributes\Permission;

#[Permission('user:create', '创建用户')]
class CreateUser extends BaseAction
{
    // ...
}

// routes/rbac.php
Route::post('/users', CreateUser::class)
    ->middleware('permission.check');  // 自动从注解读取权限
```

---

## API 接口

本扩展包提供完整的 RESTful API，默认前缀为 `/api/rbac`。

### 角色接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/roles` | 获取角色列表 |
| POST | `/roles` | 创建角色 |
| GET | `/roles/{id}` | 获取角色详情 |
| PUT | `/roles/{id}` | 更新角色 |
| DELETE | `/roles/{id}` | 删除角色 |
| POST | `/roles/{id}/permissions` | 为角色分配权限 |
| DELETE | `/roles/{id}/permissions` | 撤销角色权限（批量） |
| PUT | `/roles/{id}/permissions` | 同步角色权限 |
| POST | `/roles/{id}/data-scopes` | 为角色分配数据范围 |
| DELETE | `/roles/{id}/data-scopes` | 撤销角色数据范围（批量） |
| PUT | `/roles/{id}/data-scopes` | 同步角色数据范围 |
| POST | `/roles/{id}/instance-permissions` | 为角色分配实例权限 |
| DELETE | `/roles/{id}/instance-permissions` | 撤销角色实例权限（批量） |

---

### 权限接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/permissions` | 获取权限列表 |
| POST | `/permissions` | 创建权限 |
| GET | `/permissions/{id}` | 获取权限详情 |
| PUT | `/permissions/{id}` | 更新权限 |
| DELETE | `/permissions/{id}` | 删除权限 |
| POST | `/permissions/batch` | 批量创建权限 |
| POST | `/permissions/instance` | 创建实例权限 |

---

### 数据范围接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/data-scopes` | 获取数据范围列表 |
| POST | `/data-scopes` | 创建数据范围 |
| GET | `/data-scopes/{id}` | 获取数据范围详情 |
| PUT | `/data-scopes/{id}` | 更新数据范围 |
| DELETE | `/data-scopes/{id}` | 删除数据范围 |

---

### 用户授权接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/users/{user_id}/roles` | 为用户分配角色 |
| DELETE | `/users/{user_id}/roles` | 撤销用户角色（批量） |
| PUT | `/users/{user_id}/roles` | 同步用户角色 |
| POST | `/users/{user_id}/permissions` | 为用户分配权限 |
| DELETE | `/users/{user_id}/permissions` | 撤销用户权限（批量） |
| PUT | `/users/{user_id}/permissions` | 同步用户权限 |
| POST | `/users/{user_id}/instance-permissions` | 为用户分配实例权限 |
| DELETE | `/users/{user_id}/instance-permissions` | 撤销用户实例权限（批量） |
| POST | `/users/{user_id}/data-scopes` | 为用户分配数据范围 |
| DELETE | `/users/{user_id}/data-scopes` | 撤销用户数据范围（批量） |
| PUT | `/users/{user_id}/data-scopes` | 同步用户数据范围 |
| GET | `/users/{user_id}/permissions` | 获取用户权限 |

---

### API 响应格式

**成功响应：**
```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "id": 1,
        "name": "管理员",
        "slug": "admin"
    }
}
```

**分页响应：**
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

**错误响应：**
```json
{
    "code": 403,
    "message": "权限不足"
}
```

---

## Action 模式详解

### Action 基础

Action 是一个继承自 `BaseAction` 的类，包含三个核心部分：

1. **rules()** - 验证规则
2. **execute()** - 业务逻辑
3. **ActionContext** - 数据上下文

---

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

---

### Action 调用方式

```php
// 1. 静态调用（推荐）
$article = PublishArticle::handle([
    'title' => '文章标题',
    'content' => '文章内容',
    'category_id' => 1,
    'tags' => [1, 2, 3],
]);

// 2. 实例调用（返回 JSON 响应）
$action = new PublishArticle();
$response = $action([
    'title' => '文章标题',
    'content' => '文章内容',
]);

// 3. 路由绑定（依赖注入）
Route::post('/articles', PublishArticle::class);
```

---

### ActionContext API

```php
// 获取所有数据
$this->context->all();

// 获取单个字段（支持默认值）
$this->context->data('title', '默认标题');

// 获取额外参数（如路由参数）
$this->context->id();      // 第一个额外参数
$this->context->arg(1);    // 第二个额外参数

// 检查字段是否存在
$this->context->has('title');

// 获取指定字段
$this->context->only(['title', 'content']);
$this->context->except(['password']);
```

---

### 带权限注解的 Action

```php
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('article:*', '文章管理')]
#[Permission('article:publish', '发布文章')]
class PublishArticle extends BaseAction
{
    protected function rules(): array
    {
        return [
            'title' => 'required|string',
            'content' => 'required|string',
        ];
    }

    protected function execute()
    {
        return Article::create($this->context->all());
    }
}
```

生成权限：

```bash
php artisan rbac:scan-permissions
```

---

## 最佳实践

### 1. 权限设计原则

#### ✅ 推荐做法

**使用资源:操作格式：**
```php
'user:view'       // 查看用户
'user:create'     // 创建用户
'article:update'  // 更新文章
'order:delete'    // 删除订单
```

**权限粒度适中：**
```php
// ✅ 好的粒度
'article:publish'  // 发布文章
'article:review'   // 审核文章

// ❌ 粒度过细
'article:publish:homepage'
'article:publish:category:tech'
```

**使用权限组：**
```php
#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:view', '查看用户')]
#[Permission('user:create', '创建用户')]
#[Permission('user:update', '更新用户')]
#[Permission('user:delete', '删除用户')]
```

---

#### ❌ 避免做法

**避免硬编码权限检查：**
```php
// ❌ 不推荐
if ($user->id === 1) {
    // 管理员逻辑
}

// ✅ 推荐
if ($user->hasPermission('admin:*')) {
    // 管理员逻辑
}
```

**避免权限名称过于宽泛：**
```php
// ❌ 不推荐
'admin'
'all'

// ✅ 推荐
'user:manage'
'system:configure'
```

---

### 2. 角色设计原则

#### ✅ 推荐做法

**按职能划分角色：**
```php
CreateRole::handle(['name' => '内容编辑', 'slug' => 'editor']);
CreateRole::handle(['name' => '内容审核', 'slug' => 'reviewer']);
CreateRole::handle(['name' => '系统管理员', 'slug' => 'admin']);
```

**使用角色继承权限：**
```php
// 普通编辑
$editor->givePermission(['article:create', 'article:update']);

// 高级编辑继承普通编辑权限
$seniorEditor->givePermission(['article:create', 'article:update', 'article:publish']);
```

---

### 3. 数据范围设计原则

#### ✅ 推荐做法

**分层设计数据范围：**
```php
// 全局管理员 - 全部数据
$adminScope = CreateDataScope::handle([
    'name' => '全部数据',
    'type' => DataScopeType::ALL,
]);

// 部门经理 - 部门数据
$deptScope = CreateDataScope::handle([
    'name' => '部门数据',
    'type' => DataScopeType::DEPARTMENT,
    'config' => ['field' => 'department_id'],
]);

// 普通员工 - 个人数据
$personalScope = CreateDataScope::handle([
    'name' => '个人数据',
    'type' => DataScopeType::PERSONAL,
    'config' => ['field' => 'user_id'],
]);
```

**在模型中启用数据范围：**
```php
use Rbac\Traits\HasDataScopeScope;

class Order extends Model
{
    use HasDataScopeScope;
}

// 自动应用数据范围
$orders = Order::all();

// 临时禁用
$allOrders = Order::withoutDataScope()->get();
```

---

### 4. 实例权限使用场景

#### ✅ 适合使用实例权限

- 协作文档（多人编辑特定文档）
- 项目管理（成员访问特定项目）
- 工单系统（客服处理特定工单）
- 临时授权（临时查看某条记录）

#### ❌ 不适合使用实例权限

- 大量数据的权限控制（使用数据范围）
- 固定的角色权限（使用通用权限）
- 全局资源的权限（使用通用权限）

---

### 5. 缓存管理

**权限更新后及时清理缓存：**
```php
// 分配权限后
$user->givePermission('user:create');
$user->forgetCachedPermissions();

// 或使用命令
php artisan rbac:clear-cache
```

---

### 6. 性能优化

**使用预加载：**
```php
// ✅ 推荐
$users = User::with(['roles', 'permissions'])->get();

// ❌ 避免
$users = User::all();
foreach ($users as $user) {
    $user->roles;  // N+1 查询
}
```

**批量操作：**
```php
// ✅ 推荐
AssignPermissionsToUser::handle([
    'permission_ids' => [1, 2, 3, 4, 5],
], $userId);

// ❌ 避免
foreach ($permissionIds as $id) {
    $user->givePermission($id);
}
```

---

### 7. 自定义查询过滤器

扩展包支持通过配置注入自定义查询逻辑，实现统一的搜索规范，而不破坏核心结构。

#### 配置查询过滤器

在应用层的 `config/rbac.php` 中配置：

```php
return [
    // ... 其他配置

    /**
     * 查询过滤器回调（在执行查询前应用）
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query 查询构造器
     * @param array $params 请求参数
     * @return \Illuminate\Database\Eloquent\Builder
     */
    'query_filter' => function (\Illuminate\Database\Eloquent\Builder $query, array $params) {
        $model = $query->getModel();
        
        // 如果模型有 search scope，直接使用
        if (method_exists($model, 'scopeSearch')) {
            return $query->search($params);
        }
        
        return $query;
    },
];
```

#### 使用场景

过滤器会自动应用在所有列表查询的 Action 上：

- `ListRole` - 角色列表
- `ListPermission` - 权限列表
- `ListDataScope` - 数据范围列表
- `ListUserPermissions` - 用户权限列表

#### 实现示例

**1. 在模型中定义 `scopeSearch`：**

```php
// app/Models/Role.php
use Illuminate\Database\Eloquent\Builder;

class Role extends Model
{
    /**
     * 搜索过滤器
     */
    public function scopeSearch(Builder $query, array $params): Builder
    {
        // 关键词搜索
        if (isset($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }
        
        // 守卫名称过滤
        if (isset($params['guard_name'])) {
            $query->where('guard_name', $params['guard_name']);
        }
        
        // 状态过滤
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        return $query;
    }
}
```

**2. 或使用 SearchScopesTrait（推荐）：**

```php
// app/Models/Role.php
use App\Traits\SearchScopesTrait;

class Role extends Model
{
    use SearchScopesTrait;
    
    /**
     * 可搜索字段
     */
    protected $searchable = [
        'keyword' => ['name', 'description'],  // 模糊匹配
        'guard_name' => 'exact',                // 精确匹配
        'status' => 'exact',
    ];
}
```

**3. 前端调用：**

```http
GET /api/rbac/roles?keyword=admin&status=1&guard_name=web&per_page=20
```

**4. 底层执行：**

```php
// 扩展包内部（ListRole Action）
$query = Role::query()->withCount(['permissions', 'users']);

// 应用过滤器（自动调用 scopeSearch）
$query = $this->applyQueryFilter($query, $request->all());

// 分页
$roles = $query->paginate(20);
```

#### 优势

✅ **职责分离** - 扩展包不关心搜索实现，应用层完全控制  
✅ **代码精简** - 扩展包仅保留核心逻辑（预加载 + 过滤器 + 分页）  
✅ **灵活扩展** - 应用层可自由定义任意搜索条件  
✅ **统一规范** - 所有模型都可使用同一套 `scopeSearch` 方法  
✅ **向后兼容** - 未配置时行为不变

#### 注意事项

- 过滤器仅在列表查询时生效，不影响单个资源查询
- 过滤器接收的是验证后的参数，已通过 Action 的 `rules()` 验证
- 建议在 `scopeSearch` 中做参数校验，避免 SQL 注入
- 如果不需要搜索功能，配置中不设置 `query_filter` 即可

---

## 常见问题

### Q1: 如何实现超级管理员？

**A:** 配置超级管理员角色：

```php
// config/rbac.php
'super_admin_role' => 'super-admin',

// 创建超级管理员角色
$role = CreateRole::handle([
    'name' => '超级管理员',
    'slug' => 'super-admin',
]);

// 分配给用户
$user->assignRole($role);

// 超级管理员自动拥有所有权限
```

---

### Q2: 权限检查性能如何优化？

**A:** 
1. 启用权限缓存（默认开启）
2. 使用预加载减少查询
3. 合理设计权限粒度

```php
// config/rbac.php
'cache' => [
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
],

// 使用预加载
$users = User::with(['roles.permissions'])->get();
```

---

### Q3: 如何实现动态权限？

**A:** 使用权限注解 + 扫描命令：

```php
#[Permission('article:publish', '发布文章')]
class PublishArticle extends BaseAction {}

// 扫描生成权限
php artisan rbac:scan-permissions
```

---

### Q4: 数据范围如何调试？

**A:** 
1. 临时禁用数据范围查看完整数据
2. 检查配置的 `empty_strategy` 和 `mode`
3. 使用日志记录数据范围应用情况

```php
// 临时禁用
$allData = Model::withoutDataScope()->get();

// 查看配置
config('rbac.data_scope.empty_strategy');  // deny 或 ignore
config('rbac.data_scope.mode');            // and 或 or
```

---

### Q5: 如何批量分配权限？

**A:** 使用批量 Action：

```php
// 为角色批量分配权限
AssignPermissionsToRole::handle([
    'permission_ids' => [1, 2, 3, 4, 5],
], $roleId);

// 为用户批量分配权限
AssignPermissionsToUser::handle([
    'permission_ids' => [1, 2, 3],
], $userId);
```

---

### Q6: 实例权限过多如何清理？

**A:** 使用清理命令：

```bash
# 预览将被清理的孤立权限
php artisan rbac:clean-orphaned-permissions --dry

# 执行清理
php artisan rbac:clean-orphaned-permissions
```

---

### Q7: 如何在 Blade 模板中使用权限？

**A:** 使用 `@permission` 和 `@role` 指令：

```blade
@permission('user:create')
    <button>创建用户</button>
@endpermission

@role('admin')
    <a href="/admin">管理后台</a>
@endrole

@hasanypermission('user:view|user:create')
    <p>您拥有用户相关权限</p>
@endhasanypermission
```

---

### Q8: 如何测试权限功能？

**A:** 使用测试工厂和辅助方法：

```php
use Tests\TestCase;

class PermissionTest extends TestCase
{
    public function test_user_can_access_with_permission()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'user:view']);
        
        $role->givePermission($permission);
        $user->assignRole($role);
        
        $this->assertTrue($user->hasPermission('user:view'));
        
        $response = $this->actingAs($user)
            ->get('/users');
        
        $response->assertStatus(200);
    }
}
```

---

## 扩展阅读

- [命令文档](COMMANDS.md) - Artisan 命令详细说明
- [配置文件](../config/rbac.php) - 配置选项说明
- [API 路由](../routes/rbac.php) - RESTful API 路由定义
- [测试示例](../tests/) - 单元测试和功能测试

---

## 许可证

MIT License

---

## 贡献指南

欢迎提交 Issue 和 Pull Request！

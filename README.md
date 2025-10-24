# Laravel RBAC

<p align="center">
    <a href="https://packagist.org/packages/shiwuhao/laravel-rbac">
        <img src="https://img.shields.io/packagist/v/shiwuhao/laravel-rbac.svg?style=flat-square" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/shiwuhao/laravel-rbac">
        <img src="https://img.shields.io/packagist/php-v/shiwuhao/laravel-rbac.svg?style=flat-square" alt="PHP Version">
    </a>
    <a href="https://packagist.org/packages/shiwuhao/laravel-rbac">
        <img src="https://img.shields.io/packagist/l/shiwuhao/laravel-rbac.svg?style=flat-square" alt="License">
    </a>
</p>

> 现代化的 Laravel 12+ RBAC 扩展包，采用 Action 模式架构，提供完整的基于角色的访问控制（RBAC）和数据权限管理功能。

## ✨ 特性

- 🎯 **Action 模式架构** - 路由直接绑定 Action，无需控制器中间层
- 🔐 **完整的 RBAC 实现** - 角色、权限、数据范围管理
- 🚀 **开箱即用** - 内置完整的 CRUD Actions 和 RESTful API 路由
- 📦 **高度解耦** - 通过配置支持自定义用户模型
- 🎨 **优雅的 API** - 统一的上下文访问和响应处理
- 📝 **完善的注解** - 权限注解和 PHPDoc 注释
- 🔧 **灵活扩展** - 可发布 Actions 到项目中自定义

## 📋 版本要求

| Package | Laravel | PHP     |
|---------|---------|---------|
| 2.0.x   | 12.x    | >= 8.2  |

## 📦 安装

```bash
composer require shiwuhao/laravel-rbac
```

### 发布配置和迁移文件

```bash
# 发布所有文件
php artisan vendor:publish --provider="Rbac\RbacServiceProvider"

# 或者分别发布
php artisan vendor:publish --tag=rbac-config
php artisan vendor:publish --tag=rbac-migrations
php artisan vendor:publish --tag=rbac-routes
```

### 运行迁移

```bash
php artisan migrate
```

## 🎯 核心架构 - Action 模式

### 什么是 Action 模式？

Action 是一个独立的业务逻辑单元，每个 Action 负责一个具体的业务操作。

```php
// 路由直接绑定 Action
Route::post('/roles', CreateRole::class);
Route::put('/roles/{id}', UpdateRole::class);
```

### Action 的优势

- ✅ **单一职责** - 每个 Action 只做一件事
- ✅ **可测试性强** - 独立的类，易于单元测试
- ✅ **可复用** - 可在控制器、命令、队列中调用
- ✅ **类型安全** - 完整的类型提示和返回值定义

## 🚀 快速开始

### 配置用户模型

在 `.env` 中配置你的用户模型：

```env
RBAC_USER_MODEL=App\Models\User
```

或在 `config/rbac.php` 中配置：

```php
'models' => [
    'user' => \App\Models\User::class,
],
```

### 在用户模型中使用 Trait

```php
use Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

## 📚 API 使用

### Role（角色）

```php
use Rbac\Actions\Role\{CreateRole, UpdateRole, DeleteRole, ShowRole, ListRole};

// 创建角色
$role = CreateRole::handle([
    'name' => '管理员',
    'slug' => 'admin',
    'description' => '系统管理员',
    'guard_name' => 'web',
]);

// 更新角色
$role = UpdateRole::handle([
    'name' => '超级管理员',
], $roleId);

// 删除角色
DeleteRole::handle([], $roleId);

// 获取角色详情
$role = ShowRole::handle([], $roleId);

// 获取角色列表
$roles = ListRole::handle([
    'keyword' => 'admin',
    'per_page' => 15,
]);
```

### Permission（权限）

```php
use Rbac\Actions\Permission\{
    CreatePermission,
    BatchCreatePermissions,
    CreateInstancePermission
};

// 创建单个权限
$permission = CreatePermission::handle([
    'name' => '创建用户',
    'slug' => 'user.create',
    'resource_type' => 'user',
    'operation' => 'create',
]);

// 批量创建权限
$permissions = BatchCreatePermissions::handle([
    'resource_type' => 'article',
    'operations' => ['create', 'update', 'delete', 'view'],
]);

// 创建实例权限
$permission = CreateInstancePermission::handle([
    'resource_type' => 'article',
    'resource_id' => 1,
    'operation' => 'edit',
]);
```

### 分配权限和角色

```php
use Rbac\Actions\Role\AssignRolePermissions;
use Rbac\Actions\User\AssignRole;
use Rbac\Actions\UserPermission\AssignUserRoles;

// 给角色分配权限
AssignRolePermissions::handle([
    'permission_ids' => [1, 2, 3],
    'replace' => false, // 是否替换现有权限
], $roleId);

// 给用户分配单个角色
AssignRole::handle([
    'role_id' => 1,
], $userId);

// 批量分配角色
AssignUserRoles::handle([
    'role_ids' => [1, 2, 3],
    'replace' => true,
], $userId);
```

## 🛣️ RESTful API 路由

扩展包自动注册以下 API 路由（前缀：`/api/rbac`）：

### Role 路由
```
GET     /api/rbac/roles              # 角色列表
POST    /api/rbac/roles              # 创建角色
GET     /api/rbac/roles/{id}         # 角色详情
PUT     /api/rbac/roles/{id}         # 更新角色
DELETE  /api/rbac/roles/{id}         # 删除角色
POST    /api/rbac/roles/{id}/permissions  # 分配权限
```

### Permission 路由
```
GET     /api/rbac/permissions        # 权限列表
POST    /api/rbac/permissions        # 创建权限
GET     /api/rbac/permissions/{id}   # 权限详情
PUT     /api/rbac/permissions/{id}   # 更新权限
DELETE  /api/rbac/permissions/{id}   # 删除权限
POST    /api/rbac/permissions/batch  # 批量创建
POST    /api/rbac/permissions/instance  # 创建实例权限
```

### DataScope 路由
```
GET     /api/rbac/data-scopes        # 数据范围列表
POST    /api/rbac/data-scopes        # 创建数据范围
GET     /api/rbac/data-scopes/{id}   # 数据范围详情
PUT     /api/rbac/data-scopes/{id}   # 更新数据范围
DELETE  /api/rbac/data-scopes/{id}   # 删除数据范围
```

### User 路由
```
POST    /api/rbac/users/{user_id}/roles         # 分配角色
DELETE  /api/rbac/users/{user_id}/roles         # 撤销角色
POST    /api/rbac/users/{user_id}/roles/batch   # 批量分配
GET     /api/rbac/users/{user_id}/permissions   # 用户权限
```

## 🔒 权限检查

### 在代码中检查权限

```php
// 检查单个权限
if (auth()->user()->hasPermission('user.create')) {
    // 有权限
}

// 检查多个权限（任一）
if (auth()->user()->hasAnyPermission(['user.create', 'user.update'])) {
    // 有任一权限
}

// 检查多个权限（全部）
if (auth()->user()->hasAllPermissions(['user.create', 'user.update'])) {
    // 有全部权限
}

// 检查角色
if (auth()->user()->hasRole('admin')) {
    // 有角色
}
```

### 在 Blade 模板中

```blade
@permission('user.create')
    <button>创建用户</button>
@endpermission

@role('admin')
    <a href="/admin">管理后台</a>
@endrole

@anypermission('user.create', 'user.update')
    <button>编辑</button>
@endanypermission
```

### 使用中间件

```php
// 在路由中
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:user.view');

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');
```

## 🎨 自定义 Action

发布 Actions 到你的项目：

```bash
php artisan vendor:publish --tag=rbac-actions
```

Actions 会发布到 `app/Actions/Rbac/` 目录，你可以自由修改业务逻辑。

### 创建自定义 Action

```php
<?php

namespace App\Actions;

use Rbac\Actions\BaseAction;
use Rbac\Models\Role;

class CustomRoleAction extends BaseAction
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    protected function execute(): Role
    {
        // 通过 $this->context 访问数据
        $name = $this->context->data('name');
        $id = $this->context->id();
        
        // 你的自定义逻辑
        return Role::create(['name' => $name]);
    }
}

// 调用
$role = CustomRoleAction::handle(['name' => 'Custom']);
```

## 📖 Artisan 命令

```bash
# 创建角色
php artisan rbac:create-role admin "管理员"

# 创建权限
php artisan rbac:create-permission user.create "创建用户"

# 生成路由权限
php artisan rbac:generate-route-permissions

# 快速填充测试数据
php artisan rbac:quick-seed

# 查看 RBAC 状态
php artisan rbac:status

# 清除缓存
php artisan rbac:clear-cache
```

## ⚙️ 配置选项

```php
// config/rbac.php

return [
    // 数据表名称
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        // ...
    ],

    // 模型配置
    'models' => [
        'role' => \Rbac\Models\Role::class,
        'permission' => \Rbac\Models\Permission::class,
        'user' => \App\Models\User::class, // 自定义用户模型
    ],

    // API 路由配置
    'api' => [
        'enabled' => true,
        'prefix' => 'api/rbac',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    // 缓存配置
    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'laravel_rbac.cache',
    ],
];
```

## 🔄 从 1.x 升级到 2.0

### 主要变更

1. **Action 调用方式**
   ```php
   // 旧方式
   UpdateRole::run($data, $id);
   
   // 新方式
   UpdateRole::handle($data, $id);
   ```

2. **配置项变更**
   - `response_handler` → `response_formatter`
   - 新增 `models.user` 配置

3. **控制器移除**
   - 不再提供控制器，路由直接绑定 Action

详见 [CHANGELOG.md](CHANGELOG.md)

## 📝 License

MIT License. 详见 [LICENSE](LICENSE) 文件。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 👤 作者

- **shiwuhao** - [admin@shiwuhao.com](mailto:admin@shiwuhao.com)

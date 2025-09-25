<h1 align="center"> laravel-rbac </h1>

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
    <a href="https://github.com/shiwuhao/laravel-rbac/actions">
        <img src="https://img.shields.io/github/workflow/status/shiwuhao/laravel-rbac/Tests?style=flat-square" alt="Build Status">
    </a>
</p>

<p>laravel-rbac是一个基于Laravel 12框架的扩展包，提供了完整的RBAC（基于角色的访问控制）实现。</p>
<p>该扩展包支持模型授权，比如对菜单、分类等模型的授权。</p>
<p>Permission模型为一对一多态模型，默认提供Action模型的授权管理，并根据路由文件自动生成Action模型的权限节点。</p>

## 版本信息

Rbac  | Laravel | PHP
:------|:--------|:--------
2.0.x | 12.x    | >=8.2

## 安装方法

#### 使用composer快速安装扩展包

```shell
composer require shiwuhao/laravel-rbac
```

## 配置信息

#### 发布配置文件

```shell
php artisan vendor:publish --provider="Shiwuhao\Rbac\RbacServiceProvider"
```

会生成以下文件:
- config/rbac.php
- database/migrations/xxxx_xx_xx_xxxxxx_create_rbac_tables.php

#### 数据迁移

```shell
php artisan migrate
```

迁移后，将出现以下数据表：
- roles -- 角色表
- actions -- 操作表
- permissions -- 权限表
- role_user -- 角色和用户之间的多对多关系表
- role_permission -- 角色和权限之间的多对多关系表

## 模型

#### Role

创建角色模型 app/Models/Role.php，继承\Shiwuhao\Rbac\Models\Role

```php
<?php

namespace App\Models;

class Role extends \Shiwuhao\Rbac\Models\Role
{

}
```

#### Permission

创建权限模型 app/Models/Permission.php，继承\Shiwuhao\Rbac\Models\Permission

```php
<?php

namespace App\Models;

class Permission extends \Shiwuhao\Rbac\Models\Permission
{

}
```

#### Action

创建操作模型 app/Models/Action.php，继承\Shiwuhao\Rbac\Models\Action

```php
<?php

namespace App\Models;

class Action extends \Shiwuhao\Rbac\Models\Action
{

}
```

#### User

用户模型中 添加 UserTrait

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Shiwuhao\Rbac\Models\Traits\UserTrait;

class User extends Authenticatable
{
    use UserTrait; // 添加这个trait到你的User模型中
}
```

#### 扩展模型授权，比如菜单 Menu

创建菜单模型 app/Models/Menu.php，使用 Shiwuhao\Rbac\Models\Traits\PermissibleTrait。Menu模型的增删改会自动同步到permissions表中。

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shiwuhao\Rbac\Models\Traits\PermissibleTrait;

class Menu extends Model
{
    use PermissibleTrait;
}
```

## 使用

### Action 权限节点

Action和Permission为一对一多态模型，创建Action节点会自动同步到Permission模型

#### 创建一个Action节点

```php
$action = new App\Models\Action();
$action->name= 'user:index';
$action->label= '用户列表';
$action->method= 'get';
$action->uri= 'backend/users';
$action->save();
```

#### 批量生成Action节点

基于当前路由批量生成Action权限节点，可在config/rbac.php配置文件中通过path，except_path指定路径

```shell
# 生成权限节点
php artisan rbac:generate-permissions

# 生成权限节点并使用路由名称作为权限标识（推荐）
php artisan rbac:generate-permissions --use-route-names

# 生成权限节点并清理过时的权限
php artisan rbac:generate-permissions --clean

# 生成指定路径的权限节点
php artisan rbac:generate-permissions --path="/admin/*"

# 生成权限节点但排除某些路径
php artisan rbac:generate-permissions --except-path="/api/*"
```

### Role 角色

#### 创建一个角色

```php
$role = new App\Models\Role();
$role->name= 'Administrator';
$role->label= '超级管理员';
$role->desc= '备注';
$role->save();
```

#### 给角色绑定权限和用户

```php
$role = App\Models\Role::find(1);

// 绑定权限 (使用权限名称)
$role->permissions()->sync(['users.index', 'users.create', 'users.edit', 'users.delete']);

// 或者使用传统的URL方式
$role->permissions()->sync(['get,users', 'post,users', 'put,users/{id}', 'delete,users/{id}']);
```

### User 用户

#### 获取用户角色

```php
$user = App\Models\User::find(1);
$user->roles;
```

#### 给用户绑定角色

```php
$user->roles()->sync([1, 2, 3, 4]);// 同步
$user->roles()->attach(5);// 附加
$user->roles()->detach(5);// 分离
```

#### 获取用户拥有的权限节点

```php
$user->permissions;
```

返回数据为Collection集合，转数组可直接使用->toArray()

## 自动同步权限节点

本包支持自动同步权限节点，确保权限数据的一致性。

### 自动监听路由注册

当路由注册时，可以自动触发权限节点生成：

```php
// 在 config/rbac.php 中配置
'permission_generation' => [
    'auto_generate' => true, // 启用自动生成功能
],
```

### 自动同步权限与动作

权限和动作模型之间会自动同步：

1. 创建动作时自动创建对应的权限
2. 更新动作时自动更新对应的权限
3. 删除动作时自动删除对应的权限

### 手动同步命令

``shell
# 同步权限与动作
php artisan rbac:sync-permissions

# 同步权限并清理孤立的权限
php artisan rbac:sync-permissions --clean
```

### 调度任务

可以配置调度任务定期同步权限：

```php
// 在 App\Console\Kernel 中添加
protected function schedule(Schedule $schedule)
{
    // 每小时同步一次权限
    $schedule->command('rbac:sync-permissions')->hourly();
    
    // 每天生成一次路由权限
    $schedule->command('rbac:generate-permissions')->daily();
}
```

## 使用 Actions 模式（推荐）

本包采用 Laravel Actions 模式重构，提供了更清晰的业务逻辑组织方式。

### 创建角色

```php
use Shiwuhao\Rbac\Actions\CreateRoleAction;

$action = new CreateRoleAction();
$role = $action->execute('admin', 'Administrator', 'System administrator');
```

### 分配权限给角色

```php
use Shiwuhao\Rbac\Actions\AssignPermissionToRoleAction;

$action = new AssignPermissionToRoleAction();
$role = $action->execute($role, $permissions);
```

### 分配角色给用户

```php
use Shiwuhao\Rbac\Actions\AssignRoleToUserAction;

$action = new AssignRoleToUserAction();
$user = $action->execute($user, $roles);
```

### 检查用户权限

```php
use Shiwuhao\Rbac\Actions\CheckUserPermissionAction;

$action = new CheckUserPermissionAction();
$hasPermission = $action->execute($user, 'users.index');
```

### 使用服务类（推荐）

```php
use Shiwuhao\Rbac\Services\RbacService;

$rbac = new RbacService();

// 创建角色
$role = $rbac->createRole('admin', 'Administrator');

// 分配权限
$rbac->assignPermissionsToRole($role, $permissions);

// 分配角色给用户
$rbac->assignRolesToUser($user, [$role]);

// 检查权限
$hasPermission = $rbac->checkUserPermission($user, 'users.index');
```

### 在控制器中使用

```php
<?php

namespace App\Http\Controllers;

use Shiwuhao\Rbac\Services\RbacService;

class RoleController extends Controller
{
    protected $rbac;

    public function __construct(RbacService $rbac)
    {
        $this->rbac = $rbac;
    }

    public function store()
    {
        $role = $this->rbac->createRole(
            request('name'),
            request('label'),
            request('description', '')
        );

        return response()->json($role);
    }
}
```

## 鉴权

### 在控制器中使用

```php
// 检查用户是否有特定权限
if ($user->can('users.index')) {
    // 用户有权限
}

// 或者使用 hasPermission 方法
if ($user->hasPermission('users.index')) {
    // 用户有权限
}

// 检查多个权限 (OR 逻辑)
if ($user->hasPermission(['users.index', 'users.create'])) {
    // 用户有其中一个权限
}

// 检查多个权限 (AND 逻辑)
if ($user->hasPermission(['users.index', 'users.create'], 'name', true)) {
    // 用户同时拥有所有权限
}
```

### 在 Blade 模板中使用

``blade
@can('users.create')
    <a href="{{ route('users.create') }}">创建用户</a>
@endcan

@unless($user->hasPermission('users.delete'))
    <!-- 用户没有删除权限时显示的内容 -->
@endunless
```

### 使用中间件

在 app/Http/Kernel.php 文件中添加路由中间件

```php
protected $routeMiddleware = [
    'role' => \Shiwuhao\Rbac\Middleware\RoleMiddleware::class,
    'permission' => \Shiwuhao\Rbac\Middleware\PermissionMiddleware::class,
];
```

添加后即可在路由中使用

```php
// 使用路由名称作为权限检查
Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission');

// 或者指定特定权限
Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');

// 在路由组中使用
Route::middleware('permission')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });
});
```

## 权限验证方式对比

### 1. URL 方式（默认/向后兼容）
``php
// 权限标识格式: method,uri
'get,users'
'post,users'
'put,users/{id}'
'delete,users/{id}'
```

**优点：**
- 简单直观
- 无需额外配置

**缺点：**
- URL 变更时权限失效
- 不够语义化
- 安全性较低

### 2. 路由名称方式（推荐）
``php
// 权限标识使用路由名称
'users.index'
'users.create'
'users.edit'
'users.delete'
```

**优点：**
- 语义化强，易于理解
- 不受 URL 变更影响
- 更安全，不暴露 URL 结构
- 更易于维护

**使用方法：**
1. 为路由添加名称：
```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
```

2. 生成权限时使用路由名称：
```shell
php artisan rbac:generate-permissions --use-route-names
```

3. 在中间件中使用：
```php
Route::get('/users', [UserController::class, 'index'])
    ->name('users.index')
    ->middleware('permission');
```

## 开发和测试

### 运行测试

```shell
./vendor/bin/phpunit
```

### 代码格式化

```shell
./vendor/bin/php-cs-fixer fix
```

## 配置说明

在 `config/rbac.php` 中可以配置以下选项：

- `path`: 指定包含在权限生成中的路径模式
- `except_path`: 指定排除在权限生成之外的路径模式
- `replace_action`: 控制器名称替换规则
- `permission_generation`: 权限生成相关设置
  - `use_route_names`: 是否默认使用路由名称生成权限
  - `auto_generate`: 是否自动监听路由注册并生成权限
- `auto_sync`: 自动同步设置
  - `enabled`: 是否启用自动同步
  - `clean_orphaned`: 是否自动清理孤立的权限

## Laravel 12 特性支持

本扩展包完全支持 Laravel 12 的新特性：

- PHP 8.2+ 类型安全
- 构造函数属性提升
- 现代化的服务提供者
- 改进的中间件签名
- 更好的测试支持
- Actions 模式重构
- 自动同步权限节点

## License

MIT

```
# Laravel RBAC 权限控制系统

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shiwuhao/laravel-rbac.svg?style=flat-square)](https://packagist.org/packages/shiwuhao/laravel-rbac)
[![Total Downloads](https://img.shields.io/packagist/dt/shiwuhao/laravel-rbac.svg?style=flat-square)](https://packagist.org/packages/shiwuhao/laravel-rbac)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

一个现代化的 Laravel 12+ RBAC（基于角色的访问控制）扩展包，支持数据权限控制和自动权限同步功能。

## ✨ 特性

- 🎯 **现代化设计**: 基于 Laravel 12 和 PHP 8.2+ 构建
- 🔒 **完整的 RBAC**: 用户、角色、权限三层模型
- 📊 **数据权限控制**: 支持多种数据范围类型（全部、组织、部门、个人、自定义）
- 🔄 **自动权限同步**: 基于观察者模式的事件驱动权限同步
- 🚀 **自动路由权限**: 根据路由自动生成权限节点
- ⚡ **高性能**: 内置缓存机制和查询优化
- 🎨 **流畅接口**: 提供友好的 API 和门面
- 🛡️ **中间件支持**: 支持复杂的权限验证逻辑
- 📝 **Blade 指令**: 丰富的模板权限检查指令

## 📋 要求

- PHP 8.2+
- Laravel 12.0+

## 📦 安装

使用 Composer 安装：

```bash
composer require shiwuhao/laravel-rbac
```

### 快速安装（推荐）

使用一键安装命令，自动完成所有配置：

```bash
# 安装并创建测试数据
php artisan rbac:install --seed --demo
```

### 手动安装

如果您prefer手动安装，可以分步执行：

发布配置文件和迁移文件：

```bash
php artisan vendor:publish --provider="Shiwuhao\Rbac\RbacServiceProvider"
```

运行数据库迁移：

```bash
php artisan migrate
```

（可选）填充测试数据：

```bash
php artisan db:seed --class="Shiwuhao\Rbac\Database\Seeders\RbacSeeder"
```

## 🚀 快速开始

### 1. 配置用户模型

在你的 User 模型中使用 RBAC 特性：

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Shiwuhao\Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
    
    // 你的其他代码...
}
```

### 2. 创建角色和权限

```php
use Shiwuhao\Rbac\Facades\Rbac;

// 创建角色
$adminRole = Rbac::createRole('管理员', 'admin', '系统管理员角色');
$editorRole = Rbac::createRole('编辑', 'editor', '内容编辑角色');

// 创建权限
$userViewPermission = Rbac::createPermission(
    '查看用户',
    'user.view',
    'User',
    'view',
    '允许查看用户信息'
);

$userCreatePermission = Rbac::createPermission(
    '创建用户',
    'user.create',
    'User',
    'create',
    '允许创建新用户'
);

// 分配权限给角色
Rbac::assignPermissionToRole($adminRole, $userViewPermission);
Rbac::assignPermissionToRole($adminRole, $userCreatePermission);
Rbac::assignPermissionToRole($editorRole, $userViewPermission);
```

### 3. 分配角色给用户

```php
$user = User::find(1);

// 分配角色
$user->assignRole('admin');

// 或者直接分配权限
$user->givePermission('user.view');

// 检查权限
if ($user->hasPermission('user.create')) {
    // 用户有创建用户的权限
}
```

## 📚 使用指南

### 中间件使用

在路由中使用权限中间件：

```php
Route::middleware(['permission:user.view'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});

// 支持复杂逻辑
Route::middleware(['permission:user.view|user.manage'])->group(function () {
    // OR 逻辑：具有 user.view 或 user.manage 权限
});

Route::middleware(['permission:user.view&user.export'])->group(function () {
    // AND 逻辑：同时具有 user.view 和 user.export 权限
});
```

### Blade 指令

在 Blade 模板中使用权限检查：

```blade
@permission('user.create')
    <a href="{{ route('users.create') }}" class="btn btn-primary">创建用户</a>
@endpermission

@role('admin')
    <div class="admin-panel">管理员面板</div>
@endrole

@anypermission('user.view', 'user.manage')
    <div>具有用户查看或管理权限</div>
@endanypermission
```

### 数据权限

为模型添加数据权限特性：

```php
use Shiwuhao\Rbac\Traits\HasDataPermissions;

class Post extends Model
{
    use HasDataPermissions;
    
    // 你的其他代码...
}
```

在查询中应用数据权限：

```php
// 自动根据用户权限过滤数据
$posts = Post::withDataPermission('post.view')->get();

// 检查用户是否可以访问特定模型
if ($post->canBeAccessedBy($user, 'post.update')) {
    // 用户可以更新这篇文章
}
```

### 自动权限同步

创建自定义观察者来自动同步权限：

```php
use Shiwuhao\Rbac\Observers\PermissionSyncObserver;

class PostObserver extends PermissionSyncObserver
{
    protected function getResourceType(Model $model): string
    {
        return 'Post';
    }
    
    protected function getOperations(Model $model): array
    {
        return ['view', 'create', 'update', 'delete', 'publish'];
    }
}
```

在服务提供者中注册观察者：

```php
use App\Models\Post;
use App\Observers\PostObserver;

public function boot()
{
    Post::observe(PostObserver::class);
}
```

### 自动生成路由权限

```bash
# 生成所有路由权限
php artisan rbac:generate-route-permissions

# 按模式生成权限
php artisan rbac:generate-route-permissions --pattern="admin.*"

# 清理孤立权限
php artisan rbac:generate-route-permissions --clean
```

## 🎛️ Artisan 命令

``bash
# 创建角色
php artisan rbac:create-role "管理员" admin --description="系统管理员"

# 创建权限
php artisan rbac:create-permission "查看用户" user.view User view

# 查看 RBAC 状态
php artisan rbac:status

# 清理缓存
php artisan rbac:clear-cache
```

## ⚙️ 配置

配置文件位于 `config/rbac.php`，你可以自定义：

- 数据表名称
- 模型类
- 缓存设置
- 中间件配置
- 自动权限同步设置

## 🧪 测试

安装完成后，系统提供了完整的测试数据，包括：

- **9个角色**：从超级管理员到普通用户
- **98个权限**：覆盖用户、内容、系统等所有模块
- **5个数据范围**：全部、组织、部门、个人、自定义
- **9个演示用户**：可直接登录测试

详细测试说明请查看 [TESTING.md](TESTING.md)。

```bash
composer test
```

## 🤝 贡献

欢迎提交 Pull Request 和 Issue！

## 📄 许可证

MIT 许可证。详细信息请查看 [LICENSE](LICENSE.md) 文件。

## 📞 支持

如果你在使用过程中遇到问题，可以：

1. 查看文档
2. 提交 Issue
3. 发起 Discussion

## 🙏 致谢

感谢所有为这个项目做出贡献的开发者！

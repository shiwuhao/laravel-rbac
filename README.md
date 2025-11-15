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

---

## 📖 文档导航

| 文档 | 说明 |
|------|------|
| **[完整使用指南](docs/USAGE.md)** | 详细的 API 使用、路由配置、权限管理等 |
| **[命令行工具](docs/COMMANDS.md)** | Artisan 命令详解和使用示例 |
| **[快速开始](#-快速开始)** | 安装配置和基本用法 |
| **[更新日志](CHANGELOG.md)** | 版本变更记录 |

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

### 1. 安装依赖

```bash
composer require shiwuhao/laravel-rbac
```

### 2. 发布配置和迁移

```bash
php artisan vendor:publish --provider="Rbac\RbacServiceProvider"
php artisan migrate
```

### 3. 配置用户模型

在 `.env` 中配置：

```env
RBAC_USER_MODEL=App\Models\User
```

或在 `config/rbac.php` 中配置：

```php
'models' => [
    'user' => \App\Models\User::class,
],
```

### 4. 在用户模型中使用 Trait

```php
use Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

### 5. 开始使用

```php
use Rbac\Actions\Role\CreateRole;

// 创建角色
$role = CreateRole::handle([
    'name' => '管理员',
    'slug' => 'admin',
]);
```

> 💡 **更多详细用法请查看** → [完整使用指南](docs/USAGE.md)

## 📚 核心功能

### 角色管理

```php
use Rbac\Actions\Role\CreateRole;

$role = CreateRole::handle([
    'name' => '管理员',
    'slug' => 'admin',
]);
```

### 权限管理

```php
use Rbac\Actions\Permission\CreatePermission;

$permission = CreatePermission::handle([
    'name' => '创建用户',
    'slug' => 'user:create',
]);
```

### 权限检查

```php
// 代码中
if (auth()->user()->hasPermission('user:create')) {
    // 有权限
}

// Blade 模板中
@permission('user:create')
    <button>创建用户</button>
@endpermission

// 路由中间件
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:user:view');
```

> 📖 **详细 API 文档** → [完整使用指南](docs/USAGE.md)  
> 🔧 **命令行工具** → [Artisan 命令](docs/COMMANDS.md)

## 🎨 高级功能

### 数据范围权限

```php
use Rbac\Actions\DataScope\CreateDataScope;

// 创建数据范围
$scope = CreateDataScope::handle([
    'name' => '部门数据',
    'type' => 'department',
]);
```

### 实例权限

```php
use Rbac\Actions\Permission\CreateInstancePermission;

// 为特定文章创建权限
$permission = CreateInstancePermission::handle([
    'resource' => 'article',
    'resource_id' => 123,
    'action' => 'update',
]);
```

### 自定义 Action

```php
use Rbac\Actions\BaseAction;

class CustomAction extends BaseAction
{
    protected function rules(): array
    {
        return ['name' => 'required|string'];
    }

    protected function execute(): mixed
    {
        return $this->context->data('name');
    }
}
```

> 📖 **更多高级用法** → [完整使用指南](docs/USAGE.md)

## 🔧 Artisan 命令

```bash
# 扫描并生成权限节点
php artisan rbac:scan-permissions

# 清除权限缓存
php artisan rbac:clear-cache

# 查看权限统计
php artisan rbac:permission-stats
```

> 🔧 **完整命令列表** → [命令行工具文档](docs/COMMANDS.md)

## ⚙️ 配置选项

主要配置项：

```php
return [
    // 自定义用户模型
    'models' => [
        'user' => \App\Models\User::class,
    ],

    // API 路由配置
    'api' => [
        'enabled' => true,
        'prefix' => 'api/rbac',
        'middleware' => ['api', 'auth:sanctum'],
    ],
];
```

> 📖 **完整配置说明** → [完整使用指南](docs/USAGE.md#配置)

## 📝 License

MIT License. 详见 [LICENSE](LICENSE) 文件。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 👤 作者

- **shiwuhao** - [admin@shiwuhao.com](mailto:admin@shiwuhao.com)

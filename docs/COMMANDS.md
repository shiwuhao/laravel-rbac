# Laravel RBAC 命令文档

本文档列出 Laravel RBAC 扩展包提供的所有 Artisan 命令及其详细用法。

---

## 目录

- [权限管理命令](#权限管理命令)
- [角色管理命令](#角色管理命令)
- [系统管理命令](#系统管理命令)
- [数据清理命令](#数据清理命令)
- [推荐工作流](#推荐工作流)

---

## 权限管理命令

### rbac:scan-permissions

扫描 Action 类上的权限注解 (`#[Permission]`) 并自动生成权限节点。

**语法：**
```bash
php artisan rbac:scan-permissions [选项]
```

**选项：**
- `--force` - 强制覆盖已存在的权限
- `--dry-run` - 预览模式，仅显示将要创建的权限
- `--routes` - 同时扫描路由注解

**示例：**
```bash
# 扫描所有 Action 权限注解
php artisan rbac:scan-permissions

# 预览将要创建的权限
php artisan rbac:scan-permissions --dry-run

# 强制覆盖已存在的权限
php artisan rbac:scan-permissions --force

# 同时扫描路由注解
php artisan rbac:scan-permissions --routes
```

**工作原理：**
1. 扫描 `src/Actions` 目录下所有 Action 类
2. 查找类上的 `#[Permission(slug, name)]` 注解
3. 自动解析 `slug` 提取 `resource` 和 `action`
4. 创建或更新权限记录

**注解示例：**
```php
use Rbac\Attributes\Permission;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('user:*', '用户管理')]
#[Permission('user:create', '创建用户')]
class CreateUser extends BaseAction
{
    // ...
}
```

---

### rbac:create-permission

手动创建单个权限。

**语法：**
```bash
php artisan rbac:create-permission {name} {slug} [选项]
```

**参数：**
- `name` - 权限名称（必填）
- `slug` - 权限标识符（必填，格式：resource:action）

**选项：**
- `--resource=` - 资源类型
- `--action=` - 操作类型
- `--description=` - 权限描述
- `--guard=web` - 守卫名称（默认 web）

**示例：**
```bash
# 创建基本权限
php artisan rbac:create-permission "查看用户" user:view

# 创建完整权限
php artisan rbac:create-permission "创建订单" order:create \
  --resource=order \
  --action=create \
  --description="允许创建新订单" \
  --guard=web
```

---

### rbac:list-permissions

列出所有权限。

**语法：**
```bash
php artisan rbac:list-permissions [选项]
```

**选项：**
- `--resource=` - 按资源类型筛选
- `--action=` - 按操作类型筛选
- `--guard=` - 按守卫筛选
- `--search=` - 搜索关键词
- `--limit=20` - 显示数量限制（默认 20）

**示例：**
```bash
# 列出所有权限
php artisan rbac:list-permissions

# 列出角色相关权限
php artisan rbac:list-permissions --resource=role

# 搜索权限
php artisan rbac:list-permissions --search=create

# 列出 API 守卫权限
php artisan rbac:list-permissions --guard=api
```

---

## 角色管理命令

### rbac:create-role

创建新角色。

**语法：**
```bash
php artisan rbac:create-role {name} {slug} [选项]
```

**参数：**
- `name` - 角色名称（必填）
- `slug` - 角色标识符（必填）

**选项：**
- `--description=` - 角色描述
- `--guard=web` - 守卫名称（默认 web）
- `--permissions=` - 权限 ID 列表（逗号分隔）

**示例：**
```bash
# 创建基本角色
php artisan rbac:create-role "管理员" admin

# 创建角色并添加描述
php artisan rbac:create-role "编辑" editor \
  --description="内容编辑人员"

# 创建角色并分配权限
php artisan rbac:create-role "审核员" auditor \
  --permissions=1,2,3 \
  --description="审核内容的人员"
```

---

## 系统管理命令

### rbac:status

显示 RBAC 系统状态和统计信息。

**语法：**
```bash
php artisan rbac:status
```

**输出信息：**
- 角色总数
- 权限总数
- 数据范围总数
- 按资源类型统计权限
- 按操作类型统计权限
- 实例权限统计
- 缓存状态

**示例输出：**
```
=== RBAC 系统状态 ===

角色总数: 5
权限总数: 48
数据范围总数: 6

按资源类型统计:
  - role: 14
  - permission: 8
  - user: 12
  - data-scope: 6
  - article: 8

按操作类型统计:
  - view: 12
  - create: 10
  - update: 10
  - delete: 8
  - assign: 8
```

---

### rbac:clear-cache

清理 RBAC 系统缓存。

**语法：**
```bash
php artisan rbac:clear-cache
```

**清理内容：**
- 用户权限缓存
- 角色权限缓存
- 数据范围缓存
- 查询结果缓存

**示例：**
```bash
php artisan rbac:clear-cache
# 输出：RBAC 缓存已清理
```

---

### rbac:install

安装 RBAC 系统。

**语法：**
```bash
php artisan rbac:install [选项]
```

**选项：**
- `--force` - 强制重新安装
- `--seed` - 同时运行基础数据填充
- `--demo` - 包含演示数据

**示例：**
```bash
# 基础安装
php artisan rbac:install

# 安装并填充基础数据
php artisan rbac:install --seed

# 完整安装（包含演示数据）
php artisan rbac:install --seed --demo

# 强制重新安装
php artisan rbac:install --force
```

**执行流程：**
1. 发布配置文件到 `config/rbac.php`
2. 发布迁移文件到 `database/migrations`
3. 发布路由文件到 `routes/rbac.php`
4. 运行迁移（创建数据表）
5. （可选）填充基础权限和示例数据

---

### rbac:quick-seed

快速填充测试数据。

**语法：**
```bash
php artisan rbac:quick-seed
```

**填充内容：**
- 超级管理员角色
- 管理员角色
- 编辑角色
- 查看者角色
- 基础权限节点
- 数据范围示例

**示例：**
```bash
php artisan rbac:quick-seed
# 输出：测试数据已填充
```

---

## 数据清理命令

### rbac:clean-orphaned-permissions

清理孤立的实例权限（资源已不存在的权限）。

**语法：**
```bash
php artisan rbac:clean-orphaned-permissions [选项]
```

**选项：**
- `--dry` - 仅预览将被清理的权限，不执行删除
- `--include-soft-deletes` - 包含软删除的资源实例作为孤立项
- `--chunk=500` - 每批处理的记录数量

**示例：**
```bash
# 预览将被清理的孤立权限
php artisan rbac:clean-orphaned-permissions --dry

# 执行清理
php artisan rbac:clean-orphaned-permissions

# 清理时包含软删除的资源
php artisan rbac:clean-orphaned-permissions --include-soft-deletes

# 自定义批处理数量
php artisan rbac:clean-orphaned-permissions --chunk=1000
```

**工作原理：**
1. 扫描所有实例权限（`resource_type` 和 `resource_id` 不为空的权限）
2. 检查对应的资源模型类是否存在
3. 检查资源实例记录是否存在于数据库
4. 清理不存在的实例权限及其关联关系

**使用场景：**
- 定期清理过期的实例权限
- 删除资源后的数据维护
- 数据库优化

---

## 推荐工作流

### 开发环境初始化

```bash
# 1. 安装扩展包
composer require shiwuhao/laravel-rbac

# 2. 运行安装命令（包含演示数据）
php artisan rbac:install --seed --demo

# 3. 查看系统状态
php artisan rbac:status

# 4. 查看所有权限
php artisan rbac:list-permissions
```

---

### 权限开发工作流

```bash
# 1. 在 Action 类上添加权限注解
# 2. 扫描权限注解（预览）
php artisan rbac:scan-permissions --dry-run

# 3. 确认无误后执行生成
php artisan rbac:scan-permissions

# 4. 查看新生成的权限
php artisan rbac:list-permissions --search=新增资源

# 5. 清理缓存
php artisan rbac:clear-cache
```

---

### 生产部署工作流

```bash
# 1. 运行迁移
php artisan migrate

# 2. 扫描并生成权限
php artisan rbac:scan-permissions

# 3. 创建初始角色和权限
php artisan rbac:create-role "超级管理员" super-admin \
  --description="系统最高权限"

# 4. 清理孤立权限
php artisan rbac:clean-orphaned-permissions

# 5. 清理缓存
php artisan rbac:clear-cache

# 6. 查看系统状态
php artisan rbac:status
```

---

### 日常维护工作流

```bash
# 1. 查看系统状态
php artisan rbac:status

# 2. 定期清理孤立权限（每月）
php artisan rbac:clean-orphaned-permissions --dry  # 先预览
php artisan rbac:clean-orphaned-permissions        # 再执行

# 3. 权限更新后清理缓存
php artisan rbac:clear-cache

# 4. 验证权限完整性
php artisan rbac:list-permissions --resource=核心资源
```

---

## 命令优先级指南

### 必备命令 ⭐⭐⭐⭐⭐

| 命令 | 用途 | 使用频率 |
|------|------|----------|
| `rbac:install` | 初始化系统 | 首次安装 |
| `rbac:scan-permissions` | 自动生成权限 | 每次添加新功能 |
| `rbac:clear-cache` | 清理缓存 | 权限更新后 |

### 常用命令 ⭐⭐⭐⭐

| 命令 | 用途 | 使用频率 |
|------|------|----------|
| `rbac:status` | 查看系统状态 | 日常维护 |
| `rbac:list-permissions` | 查看权限列表 | 开发调试 |
| `rbac:create-role` | 创建角色 | 按需创建 |

### 辅助命令 ⭐⭐⭐

| 命令 | 用途 | 使用频率 |
|------|------|----------|
| `rbac:create-permission` | 手动创建权限 | 特殊场景 |
| `rbac:quick-seed` | 填充测试数据 | 开发测试 |
| `rbac:clean-orphaned-permissions` | 清理孤立权限 | 定期维护 |

---

## 常见问题

### Q1: 扫描权限时提示类不存在？

**A:** 确保 composer 自动加载已更新：
```bash
composer dump-autoload
php artisan rbac:scan-permissions
```

### Q2: 如何批量创建资源的 CRUD 权限？

**A:** 使用权限注解 + 扫描命令：
```php
#[Permission('article:view', '查看文章')]
class ViewArticle extends BaseAction {}

#[Permission('article:create', '创建文章')]
class CreateArticle extends BaseAction {}

// 然后执行
php artisan rbac:scan-permissions
```

### Q3: 清理缓存后权限不生效？

**A:** 检查中间件是否正确配置：
```php
// routes/web.php
Route::middleware(['auth', 'permission:resource:action'])->group(function () {
    // 路由
});
```

### Q4: 如何查看某个用户的所有权限？

**A:** 使用 Tinker 或代码：
```bash
php artisan tinker
>>> $user = User::find(1);
>>> $user->getAllPermissions();
```

### Q5: 权限更新后用户仍无法访问？

**A:** 清理用户权限缓存：
```bash
php artisan rbac:clear-cache
```

---

## 扩展阅读

- [使用文档](USAGE.md) - 详细的功能使用说明
- [API 文档](../README.md) - RESTful API 接口文档
- [配置说明](../config/rbac.php) - 配置文件详解

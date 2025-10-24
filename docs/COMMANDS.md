# RBAC 命令文档

本文档列出所有 RBAC 扩展包提供的 Artisan 命令及其详细说明。

## 权限生成命令

### 1. rbac:sync-routes (推荐)
**从路由自动同步生成权限节点**

```bash
php artisan rbac:sync-routes [选项]
```

**选项：**
- `--prefix=` - 路由前缀筛选，如 api/admin
- `--middleware=` - 中间件筛选，如 auth
- `--name=` - 路由名称模式，支持通配符
- `--exclude=` - 排除的路由名称模式（逗号分隔）
- `--clean` - 清理不存在的路由权限
- `--dry-run` - 预览模式

**示例：**
```bash
# 同步所有路由
php artisan rbac:sync-routes

# 只同步 API 路由
php artisan rbac:sync-routes --prefix=api

# 同步并清理
php artisan rbac:sync-routes --clean

# 预览
php artisan rbac:sync-routes --dry-run
```

### 2. rbac:scan-annotations
**扫描 Action/Controller 类的权限注解并生成权限**

```bash
php artisan rbac:scan-annotations [选项]
```

**选项：**
- `--path=*` - 扫描的路径（可多个）
- `--namespace=*` - 命名空间前缀（可多个）
- `--package` - 扫描扩展包内置的 Actions
- `--dry-run` - 预览模式
- `--force` - 强制覆盖已存在的权限

**示例：**
```bash
# 扫描项目中的 Actions 和 Controllers
php artisan rbac:scan-annotations

# 扫描扩展包内置 Actions
php artisan rbac:scan-annotations --package

# 扫描自定义路径
php artisan rbac:scan-annotations --path=app/Admin --namespace=App\\Admin
```

### 3. rbac:init-permissions
**初始化扩展包内置的权限节点**

```bash
php artisan rbac:init-permissions [选项]
```

**选项：**
- `--force` - 强制覆盖已存在的权限

**示例：**
```bash
# 初始化扩展包权限
php artisan rbac:init-permissions

# 强制更新
php artisan rbac:init-permissions --force
```

## 权限管理命令

### 4. rbac:create-permission
**手动创建单个权限**

```bash
php artisan rbac:create-permission {name} {slug} [选项]
```

**参数：**
- `name` - 权限名称（必填）
- `slug` - 权限标识符（必填）

**选项：**
- `--resource=` - 资源类型
- `--operation=` - 操作类型
- `--description=` - 权限描述
- `--guard=web` - 守卫名称（默认 web）

**示例：**
```bash
php artisan rbac:create-permission "创建用户" user:create --resource=user --operation=create
```

### 5. rbac:list-permissions
**列出所有权限**

```bash
php artisan rbac:list-permissions [选项]
```

**选项：**
- `--resource=` - 按资源类型筛选
- `--operation=` - 按操作类型筛选
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
```

## 角色管理命令

### 6. rbac:create-role
**创建新角色**

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
# 创建角色
php artisan rbac:create-role "管理员" admin --description="系统管理员"

# 创建角色并分配权限
php artisan rbac:create-role "编辑" editor --permissions=1,2,3
```

## 系统管理命令

### 7. rbac:status
**显示 RBAC 系统状态和统计信息**

```bash
php artisan rbac:status
```

**输出信息：**
- 角色总数
- 权限总数
- 数据范围总数
- 按资源类型统计
- 按操作类型统计

### 8. rbac:clear-cache
**清理 RBAC 系统缓存**

```bash
php artisan rbac:clear-cache
```

### 9. rbac:install
**安装 RBAC 系统**

```bash
php artisan rbac:install [选项]
```

**选项：**
- `--force` - 强制重新安装
- `--seed` - 同时运行测试数据填充
- `--demo` - 包含演示数据

**示例：**
```bash
# 基础安装
php artisan rbac:install

# 安装并填充测试数据
php artisan rbac:install --seed --demo
```

## 测试数据命令

### 10. rbac:quick-seed
**快速填充测试数据**

```bash
php artisan rbac:quick-seed
```

### 11. rbac:seed-test-data
**填充详细测试数据**

```bash
php artisan rbac:seed-test-data
```

### 12. rbac:generate-route-permissions
**根据路由自动生成权限节点（旧版）**

```bash
php artisan rbac:generate-route-permissions [选项]
```

**注意：** 建议使用 `rbac:sync-routes` 替代此命令。

**选项：**
- `--pattern=` - 路由名称模式（支持通配符）
- `--clean` - 清理孤立的路由权限
- `--force` - 强制重新生成

## 推荐工作流

### 开发环境
```bash
# 1. 安装
php artisan rbac:install --seed

# 2. 定义路由后同步权限
php artisan rbac:sync-routes --prefix=api --dry-run
php artisan rbac:sync-routes --prefix=api

# 3. 查看权限
php artisan rbac:list-permissions
```

### 生产部署
```bash
# 1. 运行迁移
php artisan migrate

# 2. 初始化扩展包权限
php artisan rbac:init-permissions

# 3. 同步项目路由权限
php artisan rbac:sync-routes --clean
```

### 日常维护
```bash
# 查看系统状态
php artisan rbac:status

# 清理缓存
php artisan rbac:clear-cache

# 同步权限（删除孤立权限）
php artisan rbac:sync-routes --clean
```

## 命令优先级

1. **首选：** `rbac:sync-routes` - 基于路由自动生成
2. **可选：** `rbac:scan-annotations` - 需要精细控制时使用
3. **补充：** `rbac:create-permission` - 手动创建特殊权限
4. **初始化：** `rbac:init-permissions` - 首次安装时使用

## 所有命令列表

| 命令 | 用途 | 推荐度 |
|------|------|--------|
| rbac:sync-routes | 从路由同步权限 | ⭐⭐⭐⭐⭐ |
| rbac:scan-annotations | 扫描注解生成权限 | ⭐⭐⭐⭐ |
| rbac:init-permissions | 初始化包权限 | ⭐⭐⭐⭐ |
| rbac:list-permissions | 列出权限 | ⭐⭐⭐⭐ |
| rbac:create-permission | 手动创建权限 | ⭐⭐⭐ |
| rbac:create-role | 创建角色 | ⭐⭐⭐⭐ |
| rbac:status | 查看状态 | ⭐⭐⭐⭐ |
| rbac:clear-cache | 清除缓存 | ⭐⭐⭐ |
| rbac:install | 安装系统 | ⭐⭐⭐⭐⭐ |
| rbac:quick-seed | 快速填充 | ⭐⭐⭐ |
| rbac:seed-test-data | 详细填充 | ⭐⭐ |
| rbac:generate-route-permissions | 生成路由权限(旧) | ⭐ |

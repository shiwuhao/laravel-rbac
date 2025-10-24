# Changelog

All notable changes to `laravel-rbac` will be documented in this file.

## [2.0.0] - 2024-10-24

### 🚀 Major Refactoring - Action Pattern

#### Added
- **全新 Action 模式架构**
  - 新增 `ActionContext` 上下文对象，统一数据访问
  - 新增 `BaseAction` 基类，提供 `handle()` 静态方法
  - 所有 Action 的 `execute()` 方法现在无需参数，通过 `$this->context` 访问数据

- **完整的 CRUD Actions**
  - Role 模块：CreateRole, UpdateRole, DeleteRole, ShowRole, ListRole, AssignRolePermissions
  - Permission 模块：CreatePermission, UpdatePermission, DeletePermission, ShowPermission, ListPermission, BatchCreatePermissions, CreateInstancePermission
  - DataScope 模块：CreateDataScope, UpdateDataScope, DeleteDataScope, ShowDataScope, ListDataScope
  - User 模块：AssignRole, RevokeRole
  - UserPermission 模块：AssignUserRoles, GetUserPermissions, ListUserPermissions

- **路由直接绑定 Action**
  - 移除控制器中间层
  - 路由文件直接使用 Action::class
  - 支持 RESTful API 设计

- **完善的注释和权限注解**
  - 所有 Action 都添加了 PHPDoc 注释
  - 使用 `#[Permission]` 和 `#[PermissionGroup]` 注解
  - 标准化返回值类型注解

#### Changed
- **配置文件优化**
  - 新增 `models.user` 配置项，支持自定义用户模型
  - 统一配置键名：`response_handler` → `response_formatter`
  - 移除 `actions_controllers_architecture` 配置项
  - 简化 API 路由配置注释

- **User 模型依赖解耦**
  - 所有涉及用户的 Action 改为从配置读取用户模型
  - 支持通过 `.env` 配置 `RBAC_USER_MODEL`
  - 扩展包不再依赖具体的 User 模型

- **方法命名统一**
  - 静态调用方法：`run()` → `handle()`
  - 更符合 Laravel 生态习惯

#### Removed
- **删除控制器**
  - 删除 RoleController
  - 删除 PermissionController
  - 删除 DataScopeController
  - 删除 UserPermissionController
  - 仅保留基础 Controller 类

- **删除过时接口**
  - 删除 `ActionInterface`
  - 删除 `HttpActionInterface`

- **ServiceProvider 清理**
  - 移除控制器发布配置

#### Deprecated
- `HandlesResponses` Trait 标记为废弃
  - 推荐直接使用 Action 模式
  - 仅在自定义控制器时需要

### 📚 Documentation
- 更新 README.md 反映新架构
- 添加 CHANGELOG.md 记录版本变更

### 🔧 Breaking Changes
- **Action 调用方式变更**
  ```php
  // 旧方式
  UpdateRole::run($data, $id);
  
  // 新方式
  UpdateRole::handle($data, $id);
  ```

- **Action execute 方法签名变更**
  ```php
  // 旧方式
  protected function execute(array $data, ...$args): Role
  {
      [$id] = $args;
      $role = Role::findOrFail($id);
      // ...
  }
  
  // 新方式
  protected function execute(): Role
  {
      $role = Role::findOrFail($this->context->id());
      // ...
  }
  ```

- **配置项变更**
  - `response_handler` → `response_formatter`
  - 新增必需配置 `models.user`

### 🐛 Bug Fixes
- 修复 RevokeRole 使用错误的方法（syncWithoutDetaching → detach）
- 修复配置键名不一致导致的响应格式化器加载问题

### ⚡ Performance
- 移除控制器中间层，减少调用层级
- 优化路由注册流程

---

## [1.x] - Previous Versions

请参考之前的提交记录。

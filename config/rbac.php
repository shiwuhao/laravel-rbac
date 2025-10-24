<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RBAC 数据表配置
    |--------------------------------------------------------------------------
    |
    | 配置 RBAC 系统使用的数据表名称
    |
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'data_scopes' => 'data_scopes',
        'role_permission' => 'role_permission',
        'user_role' => 'user_role',
        'user_permission' => 'user_permission',
        'permission_data_scope' => 'permission_data_scope',
        'user_data_scope' => 'user_data_scope',
    ],

    /*
    |--------------------------------------------------------------------------
    | RBAC 模型配置
    |--------------------------------------------------------------------------
    |
    | 配置 RBAC 系统使用的模型类
    |
    */
    'models' => [
        'role' => \Rbac\Models\Role::class,
        'permission' => \Rbac\Models\Permission::class,
        'data_scope' => \Rbac\Models\DataScope::class,
        'user' => env('RBAC_USER_MODEL', \App\Models\User::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    |
    | RBAC 系统的缓存设置
    |
    */
    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'laravel_rbac.cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | 超级管理员角色
    |--------------------------------------------------------------------------
    |
    | 超级管理员角色标识符，具有该角色的用户将拥有所有权限
    |
    */
    'super_admin_role' => 'super-admin',

    /*
    |--------------------------------------------------------------------------
    | API路由配置
    |--------------------------------------------------------------------------
    |
    | API 路由相关配置，路由直接绑定 Action 类
    |
    */
    'api' => [
        'enabled' => env('RBAC_API_ENABLED', true),
        'prefix' => env('RBAC_API_PREFIX', 'api/rbac'),
        'middleware' => ['api', 'auth:sanctum'],
        'name_prefix' => 'rbac.api.',
    ],

    /*
    |--------------------------------------------------------------------------
    | 自动权限同步
    |--------------------------------------------------------------------------
    |
    | 是否启用自动权限同步功能
    |
    */
    'auto_sync_permissions' => env('RBAC_AUTO_SYNC_PERMISSIONS', false),

    /*
    |--------------------------------------------------------------------------
    | 路由权限配置
    |--------------------------------------------------------------------------
    |
    | 路由权限自动生成相关配置
    |
    */
    'route_permission' => [
        // 是否自动生成路由权限
        'auto_generate' => env('RBAC_AUTO_GENERATE_ROUTE_PERMISSIONS', false),

        // 跳过的路由名称模式
        'skip_patterns' => [
            'debugbar.*',
            'telescope.*',
            'horizon.*',
            'ignition.*',
            '_ignition.*',
            'livewire.*',
            'filament.*',
            'nova.*',
        ],

        // 是否清理孤立权限
        'clean_orphaned' => env('RBAC_CLEAN_ORPHANED_PERMISSIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 中间件配置
    |--------------------------------------------------------------------------
    |
    | RBAC 中间件相关配置
    |
    */
    'middleware' => [
        'permission' => \Rbac\Middleware\PermissionMiddleware::class,
        'role' => \Rbac\Middleware\RoleMiddleware::class,
        'data_scope' => \Rbac\Middleware\DataScopeMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 权限验证配置
    |--------------------------------------------------------------------------
    |
    | 权限验证相关配置
    |
    */
    'authorization' => [
        // 是否启用权限门检查
        'enable_gates' => true,

        // 权限检查失败时的响应
        'unauthorized_response' => [
            'message' => '权限不足',
            'code' => 403,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据权限配置
    |--------------------------------------------------------------------------
    |
    | 数据权限相关配置
    |
    */
    'data_scope' => [
        // 默认数据范围类型
        'default_type' => 'personal',

        // 数据范围缓存时间（秒）
        'cache_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能优化配置
    |--------------------------------------------------------------------------
    |
    | 性能相关配置
    |
    */
    'performance' => [
        // 是否启用预加载
        'enable_eager_loading' => true,

        // 批量操作大小
        'batch_size' => 100,

        // 查询缓存时间（秒）
        'query_cache_ttl' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | 调试配置
    |--------------------------------------------------------------------------
    |
    | 调试和日志相关配置
    |
    */
    'debug' => [
        // 是否启用查询日志
        'log_queries' => env('RBAC_LOG_QUERIES', false),

        // 是否启用权限检查日志
        'log_permission_checks' => env('RBAC_LOG_PERMISSION_CHECKS', false),
    ],

    // 是否自动注册默认路由
    'register_routes' => true,

    // 响应格式化器
    'response_formatter' => \Rbac\Support\ResponseFormatter::class,
];
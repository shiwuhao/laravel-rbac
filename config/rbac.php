<?php

return [
    // 缓存时间
    'ttl' => 0,

    // 模型
    'model' => [
        'user' => 'App\Models\User',
        'role' => 'App\Models\Role',
        'action' => 'App\Models\Action',
        'permission' => 'App\Models\Permission',
    ],

    // 表名称
    'table' => [
        'users' => 'users',
        'roles' => 'roles',
        'actions' => 'actions',
        'permissions' => 'permissions',
        'role_user' => 'role_user',
        'role_permission' => 'role_permission',
    ],

    // 外键
    'foreign_key' => [
        'role' => 'role_id',
        'user' => 'user_id',
        'permission' => 'permission_id',
    ],

    // 截取短名称前缀
    'replace_action' => [
        'search' => ['App\\Http\\Controllers\\', 'Controller@', '\\'],
        'replace' => ['', ':', ''],
    ],

    // 指定路径前缀
    'path' => [
        '/backend/users',
        '/backend/roles',
        '/backend/permissions',
    ],

    // 排除路径
    'except_path' => [
        '/backend/login',
    ]
];

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

    // action labels 替换
    'action_label_replace' => [
        'index' => '列表',
        'show' => '详情',
        'store' => '新增',
        'update' => '更新',
        'destroy' => '删除',
    ],

    // controller labels 替换
    'controller_label_replace' => [
//        \App\Http\Controllers\Backend\UserController::class => '用户',
    ],

    // 指定路径前缀
    'path' => [
        '/backend/users',
        '/backend/roles',
        '/backend/permissions',
    ],

    // 排除路径
    'except_path' => [
//        '/backend/users/change',
    ]
];

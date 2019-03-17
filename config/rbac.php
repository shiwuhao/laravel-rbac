<?php

return [

    // 模型命名空间
    'model' => [
        'role' => 'App\Role',
        'user' => 'App\User',
        'permission' => 'App\Permission',
    ],

    // 表名称
    'table' => [
        'users' => 'users',
        'roles' => 'roles',
        'role_user' => 'role_user',
        'permissions' => 'permissions',
        'permission_role' => 'permission_role',
        'model_permissions' => 'model_permissions',
    ],

    // 外键
    'foreignKey' => [
        'role' => 'role_id',
        'user' => 'user_id',
        'permission' => 'permission_id',
    ],

    // 定界符
    'delimiter' => '|',

    // 控制器action label 替换
    'resourceAbilityMapLabel' => [
        'index' => '列表',
        'show' => '详情',
        'create' => '新增',
        'store' => '新增',
        'edit' => '更新',
        'update' => '更新',
        'destroy' => '删除',
        'restore' => '恢复',
    ],

    // 控制器action name 替换
    'resourceAbilityMap' => [
        'index' => 'list',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ],

    // 需要生成权限节点的控制器
    'permission' => [
        \App\Http\Controllers\RoleController::class => '角色管理',
        \App\Http\Controllers\ConfigController::class => '配置管理',
        \App\Http\Controllers\UserController::class => '用户管理',
        \App\Http\Controllers\CategoryController::class => '分类管理',
        \App\Http\Controllers\DocumentController::class => '文档管理',
        \App\Http\Controllers\PageController::class => '单页管理',
        \App\Http\Controllers\EnrollController::class => '报名管理',
        \App\Http\Controllers\GroupController::class => '组织管理',
        \App\Http\Controllers\DrawController::class => '抽奖管理',
        \App\Http\Controllers\BannerController::class => '横幅管理',
        \App\Http\Controllers\NavigateController::class => '导航管理',
        \App\Http\Controllers\DonateController::class => '捐赠管理',
        \App\Http\Controllers\TagController::class => '标签管理',
        \App\Http\Controllers\OrderController::class => '订单管理',
    ],
];

<?php

return [
    'models' => [
        'permission' => \Shiwuhao\Rbac\Models\Permission::class,

        'role' => \Shiwuhao\Rbac\Models\Role::class,

        'user' => '\App\User',
    ],

    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_user' => 'role_user',
        'permission_role' => 'permission_role',
        'data_role' => 'data_authorize',
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

    // 控制器默认方法label
    'action_label' => [
        'index' => '列表',
        'show' => '详情',
        'store' => '新增',
        'update' => '更新',
        'destroy' => '删除',
        'restore' => '恢复',
    ],

    // 方法名称替换
    'action_replace' => [
        'store' => 'create',
        'update' => 'edit',
        'destroy' => 'delete',
        'show' => 'detail',
    ],

];

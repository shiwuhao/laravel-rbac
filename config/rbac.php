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
        'roleUser' => 'role_user',
        'permissions' => 'permissions',
        'permissionRole' => 'permission_role',
        'permissionModel' => 'permission_model',
    ],

    // 外键
    'foreignKey' => [
        'role' => 'role_id',
        'user' => 'user_id',
        'permission' => 'permission_id',
    ],

    // 模型授权
    'permissionModel' => [
//        'categories' => \App\Category::class,
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
    'needGeneratePermission' => [
//        \App\Http\Controllers\RoleController::class => '角色管理',
    ],
];

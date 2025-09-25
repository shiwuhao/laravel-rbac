<?php

use Illuminate\Support\Facades\Route;
use Rbac\Http\Controllers\{RoleController, PermissionController, DataScopeController, UserPermissionController};

/*
|--------------------------------------------------------------------------
| RBAC API路由
|--------------------------------------------------------------------------
|
| 这里定义了RBAC系统的API路由。
| 控制器只负责调用Actions，所有业务逻辑都在Actions中实现。
| 这样既保持了灵活性（缓存、中间件等），又保证了代码的可维护性。
|
*/

// 角色管理路由
Route::apiResource('roles', RoleController::class)
    ->names([
        'index' => 'roles.index',
        'store' => 'roles.store',
        'show' => 'roles.show',
        'update' => 'roles.update',
        'destroy' => 'roles.destroy',
    ])
    ->middleware([
        'index' => 'permission:role.view',
        'store' => 'permission:role.create',
        'show' => 'permission:role.view',
        'update' => 'permission:role.edit',
        'destroy' => 'permission:role.delete',
    ]);

// 权限管理路由
Route::apiResource('permissions', PermissionController::class)
    ->names([
        'index' => 'permissions.index',
        'store' => 'permissions.store',
        'show' => 'permissions.show',
        'update' => 'permissions.update',
        'destroy' => 'permissions.destroy',
    ])
    ->middleware([
        'index' => 'permission:permission.view',
        'store' => 'permission:permission.create',
        'show' => 'permission:permission.view',
        'update' => 'permission:permission.edit',
        'destroy' => 'permission:permission.delete',
    ]);

// 数据范围管理路由
Route::apiResource('data-scopes', DataScopeController::class)
    ->names([
        'index' => 'data_scopes.index',
        'store' => 'data_scopes.store',
        'show' => 'data_scopes.show',
        'update' => 'data_scopes.update',
        'destroy' => 'data_scopes.destroy',
    ])
    ->middleware([
        'index' => 'permission:data_scope.view',
        'store' => 'permission:data_scope.create',
        'show' => 'permission:data_scope.view',
        'update' => 'permission:data_scope.edit',
        'destroy' => 'permission:data_scope.delete',
    ]);

// 用户权限管理路由
Route::prefix('users/{user}')->name('users.')->controller(UserPermissionController::class)->group(function () {
    Route::get('/permissions', 'show')
        ->name('permissions')
        ->middleware('permission:user.view');
    
    Route::post('/roles', 'assignRole')
        ->name('assign_role')
        ->middleware('permission:user.edit');
    
    Route::delete('/roles/{role}', 'revokeRole')
        ->name('revoke_role')
        ->middleware('permission:user.edit');
    
    Route::post('/permissions', 'grantPermission')
        ->name('grant_permission')
        ->middleware('permission:user.edit');
    
    Route::delete('/permissions/{permission}', 'revokePermission')
        ->name('revoke_permission')
        ->middleware('permission:user.edit');
});

// 权限检查路由
Route::prefix('check')->name('check.')->group(function () {
    Route::post('/permission', function (Illuminate\Http\Request $request) {
        $permission = $request->input('permission');
        return response()->json([
            'has_permission' => $request->user()->can($permission)
        ]);
    })->name('permission');
    
    Route::post('/role', function (Illuminate\Http\Request $request) {
        $role = $request->input('role');
        return response()->json([
            'has_role' => $request->user()->hasRole($role)
        ]);
    })->name('role');
    
    Route::get('/my-permissions', function (Illuminate\Http\Request $request) {
        $user = $request->user();
        return response()->json([
            'roles' => $user->roles()->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'data_scopes' => $user->getDataScopes()
        ]);
    })->name('my-permissions');
});

// 系统管理路由
Route::prefix('system')->name('system.')->group(function () {
    Route::post('/clear-cache', function () {
        \Rbac\Facades\Rbac::clearCache();
        return response()->json(['message' => '权限缓存已清除']);
    })->name('clear-cache')->middleware('permission:system.manage');
    
    Route::post('/sync-permissions', function () {
        \Illuminate\Support\Facades\Artisan::call('rbac:sync-permissions');
        return response()->json(['message' => '权限同步完成']);
    })->name('sync-permissions')->middleware('permission:system.manage');
    
    Route::post('/generate-permissions', function (Illuminate\Http\Request $request) {
        $resource = $request->input('resource');
        $actions = $request->input('actions', ['view', 'create', 'update', 'delete']);
        $guardName = $request->input('guard_name', 'web');
        
        $permissions = app(\Rbac\Services\RbacService::class)->createResourcePermissions(
            $resource,
            $actions,
            \Rbac\Enums\GuardType::from($guardName)
        );
        
        return response()->json([
            'message' => '权限节点生成完成',
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'resource' => $permission->resource
                ];
            })
        ]);
    })->name('generate-permissions')->middleware('permission:system.manage');
});
<?php

use Illuminate\Support\Facades\Route;
use Rbac\Actions\Role\{AssignRolePermissions, CreateRole, DeleteRole, ListRole, ShowRole, UpdateRole};
use Rbac\Actions\Permission\{BatchCreatePermissions, CreateInstancePermission, CreatePermission, DeletePermission, ListPermission, ShowPermission, UpdatePermission};
use Rbac\Actions\DataScope\{CreateDataScope, DeleteDataScope, ListDataScope, ShowDataScope, UpdateDataScope};
use Rbac\Actions\User\{AssignRoleToUser, RevokeRoleFromUser};
use Rbac\Actions\UserPermission\{AssignRolesToUser, GetUserPermissions, ListUserPermissions};

// Role 路由
Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', ListRole::class)->name('index');
    Route::post('/', CreateRole::class)->name('store');
    Route::get('/{id}', ShowRole::class)->name('show');
    Route::put('/{id}', UpdateRole::class)->name('update');
    Route::delete('/{id}', DeleteRole::class)->name('destroy');
    Route::post('/{id}/permissions', AssignRolePermissions::class)->name('assign-permissions');
});

// Permission 路由
Route::prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', ListPermission::class)->name('index');
    Route::post('/', CreatePermission::class)->name('store');
    Route::get('/{id}', ShowPermission::class)->name('show');
    Route::put('/{id}', UpdatePermission::class)->name('update');
    Route::delete('/{id}', DeletePermission::class)->name('destroy');
    Route::post('/batch', BatchCreatePermissions::class)->name('batch-store');
    Route::post('/instance', CreateInstancePermission::class)->name('instance-store');
});

// DataScope 路由
Route::prefix('data-scopes')->name('data-scopes.')->group(function () {
    Route::get('/', ListDataScope::class)->name('index');
    Route::post('/', CreateDataScope::class)->name('store');
    Route::get('/{id}', ShowDataScope::class)->name('show');
    Route::put('/{id}', UpdateDataScope::class)->name('update');
    Route::delete('/{id}', DeleteDataScope::class)->name('destroy');
});

// User 路由
Route::prefix('users/{user_id}')->name('users.')->group(function () {
    // 单个角色分配/撤销
    Route::post('/roles', AssignRoleToUser::class)->name('assign-role');
    Route::delete('/roles', RevokeRoleFromUser::class)->name('revoke-role');
    
    // 批量角色分配
    Route::post('/roles/batch', AssignRolesToUser::class)->name('assign-roles');
    
    // 用户权限查询
    Route::get('/permissions', GetUserPermissions::class)->name('permissions');
});

// UserPermission 路由
Route::prefix('user-permissions')->name('user-permissions.')->group(function () {
    Route::get('/', ListUserPermissions::class)->name('index');
});

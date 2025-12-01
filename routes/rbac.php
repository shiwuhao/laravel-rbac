<?php

use Illuminate\Support\Facades\Route;
use Rbac\Actions\DataScope\{CreateDataScope, DeleteDataScope, ListDataScope, ShowDataScope, UpdateDataScope};
use Rbac\Actions\Permission\{BatchCreatePermissions,
    CreateInstancePermission,
    CreatePermission,
    DeletePermission,
    ListPermission,
    ShowPermission,
    UpdatePermission};
use Rbac\Actions\Role\{
    AssignPermissionsToRole,
    AssignDataScopesToRole,
    AssignInstancePermissionToRole,
    RevokePermissionsFromRole,
    RevokeDataScopesFromRole,
    RevokeInstancePermissionsFromRole,
    SyncPermissionsToRole,
    SyncDataScopesToRole,
    CreateRole,
    DeleteRole,
    ListRole,
    ShowRole,
    UpdateRole
};
use Rbac\Actions\User\{
    AssignRolesToUser,
    RevokeRolesFromUser,
    SyncRolesToUser,
    AssignPermissionsToUser,
    RevokePermissionsFromUser,
    SyncPermissionsToUser,
    AssignDataScopesToUser,
    RevokeDataScopesFromUser,
    SyncDataScopesToUser,
    AssignInstancePermissionToUser,
    RevokeInstancePermissionsFromUser,
    GetUserPermissions,
    ListUserPermissions
};

// Role 路由（基于注解自动校验）
Route::prefix('roles')->name('roles.')->middleware('permission.check')->group(function () {
    Route::get('/', ListRole::class)->name('index');
    Route::post('/', CreateRole::class)->name('store');
    Route::get('/{id}', ShowRole::class)->name('show');
    Route::put('/{id}', UpdateRole::class)->name('update');
    Route::delete('/{id}', DeleteRole::class)->name('destroy');
    // 权限管理
    Route::post('/{id}/permissions', AssignPermissionsToRole::class)->name('assign-permissions');
    Route::delete('/{id}/permissions', RevokePermissionsFromRole::class)->name('revoke-permissions');
    Route::put('/{id}/permissions', SyncPermissionsToRole::class)->name('sync-permissions');

    // 数据范围管理
    Route::post('/{id}/data-scopes', AssignDataScopesToRole::class)->name('assign-data-scopes');
    Route::delete('/{id}/data-scopes', RevokeDataScopesFromRole::class)->name('revoke-data-scopes');
    Route::put('/{id}/data-scopes', SyncDataScopesToRole::class)->name('sync-data-scopes');

    // 实例权限管理（支持单个或批量）
    Route::post('/{id}/instance-permissions', AssignInstancePermissionToRole::class)->name('assign-instance-permissions');
    Route::delete('/{id}/instance-permissions', RevokeInstancePermissionsFromRole::class)->name('revoke-instance-permissions');
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
    // 角色管理
    Route::post('/roles', AssignRolesToUser::class)->name('assign-roles');
    Route::delete('/roles', RevokeRolesFromUser::class)->name('revoke-roles');
    Route::put('/roles', SyncRolesToUser::class)->name('sync-roles');

    // 直接权限管理
    Route::post('/permissions', AssignPermissionsToUser::class)->name('assign-permissions');
    Route::delete('/permissions', RevokePermissionsFromUser::class)->name('revoke-permissions');
    Route::put('/permissions', SyncPermissionsToUser::class)->name('sync-permissions');

    // 实例权限管理（支持单个或批量）
    Route::post('/instance-permissions', AssignInstancePermissionToUser::class)->name('assign-instance-permissions');
    Route::delete('/instance-permissions', RevokeInstancePermissionsFromUser::class)->name('revoke-instance-permissions');

    // 数据范围管理
    Route::post('/data-scopes', AssignDataScopesToUser::class)->name('assign-data-scopes');
    Route::delete('/data-scopes', RevokeDataScopesFromUser::class)->name('revoke-data-scopes');
    Route::put('/data-scopes', SyncDataScopesToUser::class)->name('sync-data-scopes');

    // 权限查询
    Route::get('/permissions', GetUserPermissions::class)->name('get-permissions');
});

// UserPermission 路由
Route::prefix('user-permissions')->name('user-permissions.')->group(function () {
    Route::get('/', ListUserPermissions::class)->name('index');
    Route::get('/{user_id}', GetUserPermissions::class)->name('get-user-permissions');
});

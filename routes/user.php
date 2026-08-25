<?php

use App\Http\Controllers\Admin\{ UserController, RoleController, PermissionController, DepartmentController };

// Permissions Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'permissions'])->name('admin.permissions');
    Route::get('/permissions/data', [PermissionController::class, 'getPermissions'])->name('admin.permissions.data');
    Route::post('/permissions', [PermissionController::class, 'storePermission'])->name('admin.permissions.store');
    Route::put('/permissions/{id}', [PermissionController::class, 'updatePermission'])->name('admin.permissions.update');
    Route::delete('/permissions/{id}', [PermissionController::class, 'deletePermission'])->name('admin.permissions.delete');
});


// Roles Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles');
    Route::get('/roles/data', [RoleController::class, 'getRoles'])->name('admin.roles.data');
    Route::get('/roles/permissions/all', [RoleController::class, 'getPermissions'])->name('admin.roles.permissions');
    Route::get('/roles/{id}', [RoleController::class, 'getRole'])->name('admin.roles.get');
    Route::get('/roles/{id}/users', [RoleController::class, 'getRoleUsers'])->name('admin.roles.users');
    Route::post('/roles', [RoleController::class, 'storeRole'])->name('admin.roles.store');
    Route::put('/roles/{id}', [RoleController::class, 'updateRole'])->name('admin.roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'deleteRole'])->name('admin.roles.delete');
});



// Department Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('admin.departments');
    Route::get('/departments/data', [DepartmentController::class, 'getData'])->name('admin.departments.data');
    Route::get('/departments/all', [DepartmentController::class, 'getAll'])->name('admin.departments.all');
    Route::get('/departments/users', [DepartmentController::class, 'getUsers'])->name('admin.departments.users');
    Route::get('/departments/{id}', [DepartmentController::class, 'show'])->name('admin.departments.show');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])->name('admin.departments.update');
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
    Route::post('/departments/{id}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('admin.departments.toggle-status');
});


// User Management Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/data', [UserController::class, 'getUsers'])->name('users.data');
    Route::get('/users/roles/all', [UserController::class, 'getRoles'])->name('users.roles');
    Route::get('/users/permissions/all', [UserController::class, 'getPermissions'])->name('users.permissions.all');
    Route::get('/users/departments', [UserController::class, 'getDepartments'])->name('users.departments');  // MOVED BEFORE {id}
    Route::get('/users/{id}/permissions', [UserController::class, 'getUserPermissions'])->name('users.permissions');
    Route::post('/users/{id}/assign-permission', [UserController::class, 'assignPermission'])->name('users.assign-permission');
    Route::post('/users/{id}/revoke-permission', [UserController::class, 'revokePermission'])->name('users.revoke-permission');
    Route::get('/users/{id}', [UserController::class, 'getUser'])->name('users.get');  // WILDCARD LAST
    Route::post('/users', [UserController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'deleteUser'])->name('users.delete');
    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleUserStatus'])->name('users.toggle-status');
});
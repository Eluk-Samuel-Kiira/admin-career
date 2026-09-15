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


use App\Http\Controllers\JobSeeker\SeekerController;

Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Job Seekers Management
    Route::get('/seekers', [SeekerController::class, 'index'])->name('admin.seekers.index');
    Route::get('/seekers/data', [SeekerController::class, 'getData'])->name('admin.seekers.data');
    Route::get('/seekers/filters', [SeekerController::class, 'getFilters'])->name('admin.seekers.filters');
    Route::get('/seekers/{id}', [SeekerController::class, 'show'])->name('admin.seekers.show');
    Route::get('/seekers/{id}/cv', [SeekerController::class, 'viewCv'])->name('admin.seekers.cv');
    Route::get('/seekers/{id}/applications', [SeekerController::class, 'applications'])->name('admin.seekers.applications');
});



use App\Http\Controllers\Service\ServiceController;

Route::prefix('admin')->middleware(['auth'])->group(function () {

    // Services
    Route::get('/services', [ServiceController::class, 'index'])
        ->name('admin.services');
    Route::get('/services/data', [ServiceController::class, 'getData'])
        ->name('admin.services.data');
    Route::get('/services/{id}', [ServiceController::class, 'show'])
        ->name('admin.services.show');
    Route::post('/services', [ServiceController::class, 'store'])
        ->name('admin.services.store');
    Route::put('/services/{id}', [ServiceController::class, 'update'])
        ->name('admin.services.update');
    Route::delete('/services/{id}', [ServiceController::class, 'destroy'])
        ->name('admin.services.destroy');
    Route::post('/services/{id}/toggle-status', [ServiceController::class, 'toggleStatus'])
        ->name('admin.services.toggle-status');
});


use App\Http\Controllers\Service\ServicePriceController;

Route::prefix('admin')->middleware(['auth'])->group(function () {

    // Service Prices
    Route::get('/service-prices', [ServicePriceController::class, 'index'])
        ->name('admin.service-prices');
    Route::get('/service-prices/data', [ServicePriceController::class, 'getData'])
        ->name('admin.service-prices.data');
    Route::get('/service-prices/services', [ServicePriceController::class, 'getServices'])
        ->name('admin.service-prices.services');
    Route::get('/service-prices/countries', [ServicePriceController::class, 'getCountries'])
        ->name('admin.service-prices.countries');
    Route::get('/service-prices/currencies', [ServicePriceController::class, 'getCurrencies'])
        ->name('admin.service-prices.currencies');
    Route::get('/service-prices/{id}', [ServicePriceController::class, 'show'])
        ->name('admin.service-prices.show');
    Route::post('/service-prices', [ServicePriceController::class, 'store'])
        ->name('admin.service-prices.store');
    Route::put('/service-prices/{id}', [ServicePriceController::class, 'update'])
        ->name('admin.service-prices.update');
    Route::delete('/service-prices/{id}', [ServicePriceController::class, 'destroy'])
        ->name('admin.service-prices.destroy');
    Route::post('/service-prices/{id}/toggle-status', [ServicePriceController::class, 'toggleStatus'])
        ->name('admin.service-prices.toggle-status');
});


use App\Http\Controllers\Service\CvReviewRequestController;

Route::prefix('admin')->middleware(['auth'])->group(function () {

    // CV Review Requests
    Route::get('/cv-review-requests', [CvReviewRequestController::class, 'index'])
        ->name('admin.cv-review-requests');
    Route::get('/cv-review-requests/data', [CvReviewRequestController::class, 'getData'])
        ->name('admin.cv-review-requests.data');
    Route::get('/cv-review-requests/stats', [CvReviewRequestController::class, 'stats'])
        ->name('admin.cv-review-requests.stats');
    Route::get('/cv-review-requests/filters', [CvReviewRequestController::class, 'filters'])
        ->name('admin.cv-review-requests.filters');
    Route::get('/cv-review-requests/{id}/detail', [CvReviewRequestController::class, 'detail'])
        ->name('admin.cv-review-requests.detail');
    Route::get('/cv-review-requests/{id}', [CvReviewRequestController::class, 'show'])
        ->name('admin.cv-review-requests.show');
    Route::put('/cv-review-requests/{id}', [CvReviewRequestController::class, 'update'])
        ->name('admin.cv-review-requests.update');
    Route::post('/cv-review-requests/{id}/status', [CvReviewRequestController::class, 'updateStatus'])
        ->name('admin.cv-review-requests.status');
    Route::post('/cv-review-requests/{id}/confirm-payment', [CvReviewRequestController::class, 'confirmPayment'])
        ->name('admin.cv-review-requests.confirm-payment');
    Route::post('/cv-review-requests/{id}/upload-delivered', [CvReviewRequestController::class, 'uploadDelivered'])
        ->name('admin.cv-review-requests.upload-delivered');
    Route::delete('/cv-review-requests/{id}', [CvReviewRequestController::class, 'destroy'])
        ->name('admin.cv-review-requests.destroy');

    Route::post('/cv-review-requests/{id}/run-ai-review', [CvReviewRequestController::class, 'runAiReview'])
        ->name('admin.cv-review-requests.run-ai-review');
    Route::post('/cv-review-requests/{id}/save-edited-review', [CvReviewRequestController::class, 'saveEditedReview'])
        ->name('admin.cv-review-requests.save-edited-review');
    Route::post('/cv-review-requests/{id}/deliver', [CvReviewRequestController::class, 'deliver'])
        ->name('admin.cv-review-requests.deliver');
});
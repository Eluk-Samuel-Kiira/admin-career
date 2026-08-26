<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Jobs\{ CountryController,CompanyController, JobController, 
    LocationController, CategoryController };
    
use App\Http\Controllers\Api\Pages\PageController;
use App\Http\Controllers\Api\Blog\{BlogController};

use App\Http\Controllers\Api\Auth\LoginTokenController;


// ✅ TEST ROUTE
Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'Pong!',
        'timestamp' => now()
    ]);
});

// ✅ Protected routes with middleware
Route::middleware(['verifycountry'])->group(function () {
    
    // Country route
    Route::get('/countries/{code}', [CountryController::class, 'show']);
    
    // Job routes - All in one controller
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::get('/categories', [JobController::class, 'categories']);
    Route::get('/locations', [JobController::class, 'locations']);
    Route::get('/companies', [JobController::class, 'companies']);

    Route::post('jobs/{id}/track-application', [JobController::class, 'trackApplication']);



    // Company routes
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/featured', [CompanyController::class, 'featured']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::get('/industries', [CompanyController::class, 'industries']);
    Route::get('/job-types', [JobController::class, 'jobTypes']);

    // Categories routes
    Route::get('/all_categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{id}/jobs', [CategoryController::class, 'jobs']);

    // Location routes
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{identifier}', [LocationController::class, 'show']);
    Route::get('/locations/{identifier}/jobs', [LocationController::class, 'jobs']);


    // Pages routes
    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/pages/{slug}', [PageController::class, 'show']);
    Route::get('/pages/template/{template}', [PageController::class, 'byTemplate']);
    Route::get('/pages/featured', [PageController::class, 'featured']);

    Route::get('/social-media/featured', [PageController::class, 'getFeatured'])
    ->name('api.social-media.featured');
    Route::get('/social-media/country/{countryCode}', [PageController::class, 'getByCountry'])
        ->name('api.social-media.by-country');


    Route::get('/blogs', [BlogController::class, 'index']);
    Route::get('/blogs/categories', [BlogController::class, 'categories']);
    Route::get('/blogs/{identifier}', [BlogController::class, 'show']);
    Route::post('/blogs/{id}/view', [BlogController::class, 'trackView']);



    // Auth Routes
    Route::prefix('auth')->group(function () {
        // Public routes
        Route::post('/register', [LoginTokenController::class, 'registerApi']);
        Route::post('/send-login-link', [LoginTokenController::class, 'sendLoginLinkApi']);
        Route::post('/verify-token', [LoginTokenController::class, 'verifyToken']);
    });

    // Protected routes (require Sanctum token)
    Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
        Route::post('/logout', [LoginTokenController::class, 'logoutApi']);
        Route::get('/user', [LoginTokenController::class, 'userApi']);
    });

});

// Route to test middleware
Route::get('/test-auth', function () {
    return response()->json([
        'success' => true,
        'message' => 'Authentication successful!'
    ]);
})->middleware(['verifycountry']);
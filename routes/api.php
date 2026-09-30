<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Jobs\{ CountryController,CompanyController, JobController, 
    LocationController, CategoryController, JobActionController };
    
use App\Http\Controllers\Api\Pages\PageController;
use App\Http\Controllers\Api\Blog\{BlogController};

use App\Http\Controllers\Api\Auth\{ LoginTokenController, ProfileController, CvController, EmployerProfileController };
use App\Http\Controllers\Api\Service\{ CvReviewRequestController };


use App\Http\Controllers\Api\Employer\{ AnalyticsController, ComplianceController, JobSubmissionController, AtsController, DashboardController };

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
        // Route::post('/logout', [LoginTokenController::class, 'logoutApi']);
        // Route::get('/user', [LoginTokenController::class, 'userApi']);
    });

    Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
        Route::get('/user', [ProfileController::class, 'userApi']);
        Route::post('/logout', [ProfileController::class, 'logoutApi']);
        
        // Profile update routes
        Route::put('/user/update', [ProfileController::class, 'updateUserApi']);
        Route::post('/user/avatar', [ProfileController::class, 'updateAvatarApi']);
        Route::post('/user/cv', [CvController::class, 'upload']);

        Route::get('/user/cv', [CvController::class, 'list']);            
        Route::delete('/user/cv', [CvController::class, 'delete']);

    });

    
    Route::post('jobs/{id}/track-application-guest', [JobController::class, 'trackApplication']);

    Route::middleware(['auth:sanctum'])->prefix('job-action')->group(function () {
        Route::post('/{id}/save', [JobActionController::class, 'toggleSave']);
        Route::post('/{id}/track-application', [JobActionController::class, 'trackApplication']);
        Route::get('/{id}/status', [JobActionController::class, 'getJobStatus']);
        Route::get('/saved', [JobActionController::class, 'getSavedJobs']);
        Route::get('/applied', [JobActionController::class, 'getAppliedJobs']);
        Route::put('/{id}/application-status', [JobActionController::class, 'updateApplicationStatus']);
    });



    Route::middleware(['auth:sanctum'])->prefix('cv-review')->group(function () {
        // Quote price BEFORE submitting
        Route::get('/quote', [CvReviewRequestController::class, 'quote']);

        // Submit new request (with file OR existing_cv_path)
        Route::post('/', [CvReviewRequestController::class, 'store']);

        // Seeker's list + single
        Route::get('/', [CvReviewRequestController::class, 'index']);
        Route::get('/{uuid}', [CvReviewRequestController::class, 'show']);

        // Answer the AI gap questions
        Route::post('/{uuid}/answers', [CvReviewRequestController::class, 'submitAnswers']);

        // Payment
        Route::post('/{uuid}/pay', [CvReviewRequestController::class, 'initPayment']);
        Route::post('/{uuid}/revision', [CvReviewRequestController::class, 'requestRevision']);
        Route::delete('/{uuid}', [CvReviewRequestController::class, 'destroy']);
    });

    // Payment gateway callback — unauthenticated, protected by signature in real life
    Route::post('/cv-review/payment-callback', [CvReviewRequestController::class, 'paymentCallback'])
        ->name('api.cv-review.callback');



    Route::middleware(['auth:sanctum'])->prefix('employer')->group(function () {
        Route::get('/profile',              [EmployerProfileController::class, 'show']);
        Route::post('/profile',             [EmployerProfileController::class, 'update']);
        Route::post('/profile/logo',        [EmployerProfileController::class, 'uploadLogo']);
        Route::delete('/profile/logo',      [EmployerProfileController::class, 'deleteLogo']);
    });



    Route::middleware(['auth:sanctum'])->prefix('employer')->group(function () {
        // ... existing profile routes

        Route::get('/documents',              [ComplianceController::class, 'index']);
        Route::post('/documents/{type}',      [ComplianceController::class, 'upload']);
        Route::delete('/documents/{type}',    [ComplianceController::class, 'destroy']);
        Route::post('/documents/submit',      [ComplianceController::class, 'submit']);
    });



    Route::middleware(['auth:sanctum'])->prefix('employer')->group(function () {
        // ... existing profile + compliance routes

        // Job Submissions
        Route::get('/job-packages',         [JobSubmissionController::class, 'packages']);
        Route::get('/job-submissions',      [JobSubmissionController::class, 'index']);
        Route::get('/job-submissions/counts', [JobSubmissionController::class, 'counts']);
        Route::post('/job-submissions',     [JobSubmissionController::class, 'store']);
        Route::get('/job-submissions/{uuid}',    [JobSubmissionController::class, 'show']);
        Route::post('/job-submissions/{uuid}/payment', [JobSubmissionController::class, 'recordPayment']);
        Route::post('/job-submissions/{uuid}/cancel',  [JobSubmissionController::class, 'cancel']);
        Route::delete('/job-submissions/{uuid}',       [JobSubmissionController::class, 'destroy']);
    });


    Route::middleware(['auth:sanctum'])->prefix('employer/ats')->group(function () {
        Route::get('/batches/{uuid}',            [AtsController::class, 'batchStatus']);   // ← FIRST
        Route::get('/{slug}/applicants',         [AtsController::class, 'index']);
        Route::get('/{slug}/applicants/export',  [AtsController::class, 'export']);
        Route::post('/{slug}/applicants/bulk',   [AtsController::class, 'bulkUpdate']);
        Route::post('/{slug}/screen',            [AtsController::class, 'screen']);
        Route::get('/{slug}/applicants/{id}',    [AtsController::class, 'show']);
        Route::put('/{slug}/applicants/{id}',    [AtsController::class, 'update']);
        Route::get('/{slug}/applicants/{id}/cv', [AtsController::class, 'downloadCv'])
            ->name('api.employer.ats.download-cv');
    });


    Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
        Route::get('/employer', [DashboardController::class, 'index']);
        Route::get('/seeker',   [DashboardController::class, 'seeker']);
    });


    
    Route::middleware(['auth:sanctum'])->prefix('employer/analytics')->group(function () {
        Route::get('/filters', [AnalyticsController::class, 'filters']);
        Route::get('/data',    [AnalyticsController::class, 'data']);
    });
    Route::get('/filters/dropdowns', [\App\Http\Controllers\Api\Filters\FilterController::class, 'dropdowns']);


    Route::get('/filters/dropdowns', [\App\Http\Controllers\Api\Filters\FilterController::class, 'dropdowns']);

    // Employer CV filtering (country-scoped)
    Route::prefix('seekers')->group(function () {
        Route::get('/filter',        [\App\Http\Controllers\Api\Filters\SeekerCVFilterController::class, 'index']);
        Route::get('/{id}',          [\App\Http\Controllers\Api\Filters\SeekerCVFilterController::class, 'show']);
    });

    Route::middleware(['auth:sanctum'])->prefix('letters')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Api\Service\LetterController::class, 'index']);
        Route::get('/companies',     [\App\Http\Controllers\Api\Service\LetterController::class, 'companies']);
        Route::get('/search-jobs',   [\App\Http\Controllers\Api\Service\LetterController::class, 'searchJobs']);
        Route::post('/',             [\App\Http\Controllers\Api\Service\LetterController::class, 'store']);
        Route::get('/{uuid}',        [\App\Http\Controllers\Api\Service\LetterController::class, 'show']);
        Route::post('/{uuid}/pay',   [\App\Http\Controllers\Api\Service\LetterController::class, 'pay']);
        Route::get('/{uuid}/download', [\App\Http\Controllers\Api\Service\LetterController::class, 'download']);
        Route::delete('/{uuid}',     [\App\Http\Controllers\Api\Service\LetterController::class, 'destroy']);
    });

    Route::get('/services/pricing', [\App\Http\Controllers\Api\Service\PricingController::class, 'index']);

});

// Route to test middleware
Route::get('/test-auth', function () {
    return response()->json([
        'success' => true,
        'message' => 'Authentication successful!'
    ]);
})->middleware(['verifycountry']);
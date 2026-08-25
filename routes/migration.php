<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Job\Migration\{ 
    DatabaseMigrationController,
};
use Illuminate\Support\Facades\Route;




// In routes/web.php
Route::middleware(['auth', 'superadmin'])->prefix('admin')->group(function () {
    // Database Migration
    Route::get('/migration', [DatabaseMigrationController::class, 'index'])->name('admin.migration.dashboard');
    Route::get('/migration/tables', [DatabaseMigrationController::class, 'getTables'])->name('admin.migration.tables');
    Route::get('/migration/stats', [DatabaseMigrationController::class, 'getStats'])->name('admin.migration.stats');
    Route::get('/migration/all-stats', [DatabaseMigrationController::class, 'getAllStats'])->name('admin.migration.all-stats');
    Route::get('/migration/progress', [DatabaseMigrationController::class, 'getProgress'])->name('admin.migration.progress');
    Route::get('/migration/summary', [DatabaseMigrationController::class, 'getSummary'])->name('admin.migration.summary');
    Route::get('/migration/table-config/{table}', [DatabaseMigrationController::class, 'getTableConfig'])->name('admin.migration.table-config');
    Route::get('/migration/test-connection', [DatabaseMigrationController::class, 'testConnection'])->name('admin.migration.test-connection');
    Route::get('/migration/debug-data', [DatabaseMigrationController::class, 'debugData'])->name('admin.migration.debug-data');
    Route::get('/migration/check-legacy', [DatabaseMigrationController::class, 'checkLegacyConnection'])->name('admin.migration.check-legacy');
    Route::post('/migration/migrate', [DatabaseMigrationController::class, 'migrate'])->name('admin.migration.migrate');
    Route::post('/migration/migrate-all', [DatabaseMigrationController::class, 'migrateAll'])->name('admin.migration.migrate-all');
    Route::post('/migration/reset', [DatabaseMigrationController::class, 'resetMigration'])->name('admin.migration.reset');
    Route::get('/migration/countries', [DatabaseMigrationController::class, 'getCountries'])->name('admin.migration.countries');
});


//Job Settings

Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Salary Ranges - Resource routes with explicit methods
    Route::get('/salary-ranges', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'index'])
        ->name('admin.salary-ranges');
    Route::get('/salary-ranges/data', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'getData'])
        ->name('admin.salary-ranges.data');
    Route::get('/salary-ranges/{id}', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'show'])
        ->name('admin.salary-ranges.show');
    Route::post('/salary-ranges', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'store'])
        ->name('admin.salary-ranges.store');
    
    // ADD PUT METHOD SUPPORT
    Route::put('/salary-ranges/{id}', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'update'])
        ->name('admin.salary-ranges.update');
    
    // Also keep POST with _method=PUT for form spoofing
    Route::post('/salary-ranges/{id}', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'update'])
        ->name('admin.salary-ranges.update-post');
    
    Route::delete('/salary-ranges/{id}', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'destroy'])
        ->name('admin.salary-ranges.destroy');
    Route::post('/salary-ranges/{id}/toggle-status', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'toggleStatus'])
        ->name('admin.salary-ranges.toggle-status');
    Route::get('/salary-ranges/countries', [App\Http\Controllers\Job\Setting\SalaryRangeController::class, 'getCountries'])
        ->name('admin.salary-ranges.countries');
});


Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Education Levels
    Route::get('/education-levels', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'index'])
        ->name('admin.education-levels');
    Route::get('/education-levels/data', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'getData'])
        ->name('admin.education-levels.data');
    Route::get('/education-levels/{id}', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'show'])
        ->name('admin.education-levels.show');
    Route::post('/education-levels', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'store'])
        ->name('admin.education-levels.store');
    Route::put('/education-levels/{id}', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'update'])
        ->name('admin.education-levels.update');
    Route::post('/education-levels/{id}', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'update'])
        ->name('admin.education-levels.update-post');
    Route::delete('/education-levels/{id}', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'destroy'])
        ->name('admin.education-levels.destroy');
    Route::post('/education-levels/{id}/toggle-status', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'toggleStatus'])
        ->name('admin.education-levels.toggle-status');
    Route::get('/education-levels/countries', [App\Http\Controllers\Job\Setting\EducationLevelController::class, 'getCountries'])
        ->name('admin.education-levels.countries');
});


// In routes/web.php
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Experience Levels
    Route::get('/experience-levels', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'index'])
        ->name('admin.experience-levels');
    Route::get('/experience-levels/data', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'getData'])
        ->name('admin.experience-levels.data');
    Route::get('/experience-levels/{id}', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'show'])
        ->name('admin.experience-levels.show');
    Route::post('/experience-levels', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'store'])
        ->name('admin.experience-levels.store');
    Route::put('/experience-levels/{id}', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'update'])
        ->name('admin.experience-levels.update');
    Route::post('/experience-levels/{id}', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'update'])
        ->name('admin.experience-levels.update-post');
    Route::delete('/experience-levels/{id}', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'destroy'])
        ->name('admin.experience-levels.destroy');
    Route::post('/experience-levels/{id}/toggle-status', [App\Http\Controllers\Job\Setting\ExperienceLevelController::class, 'toggleStatus'])
        ->name('admin.experience-levels.toggle-status');
});

Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Countries
    Route::get('/countries', [App\Http\Controllers\Job\Setting\CountryController::class, 'index'])
        ->name('admin.countries');
    Route::get('/countries/data', [App\Http\Controllers\Job\Setting\CountryController::class, 'getData'])
        ->name('admin.countries.data');
    Route::get('/countries/active', [App\Http\Controllers\Job\Setting\CountryController::class, 'getActiveCountries'])
        ->name('admin.countries.active');
    Route::get('/countries/{id}', [App\Http\Controllers\Job\Setting\CountryController::class, 'show'])
        ->name('admin.countries.show');
    Route::post('/countries', [App\Http\Controllers\Job\Setting\CountryController::class, 'store'])
        ->name('admin.countries.store');
    Route::put('/countries/{id}', [App\Http\Controllers\Job\Setting\CountryController::class, 'update'])
        ->name('admin.countries.update');
    Route::post('/countries/{id}', [App\Http\Controllers\Job\Setting\CountryController::class, 'update'])
        ->name('admin.countries.update-post');
    Route::delete('/countries/{id}', [App\Http\Controllers\Job\Setting\CountryController::class, 'destroy'])
        ->name('admin.countries.destroy');
    Route::post('/countries/{id}/toggle-status', [App\Http\Controllers\Job\Setting\CountryController::class, 'toggleStatus'])
        ->name('admin.countries.toggle-status');
});

Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Job Types
    Route::get('/job-types', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'index'])
        ->name('admin.job-types');
    Route::get('/job-types/data', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'getData'])
        ->name('admin.job-types.data');
    Route::get('/job-types/{id}', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'show'])
        ->name('admin.job-types.show');
    Route::post('/job-types', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'store'])
        ->name('admin.job-types.store');
    Route::put('/job-types/{id}', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'update'])
        ->name('admin.job-types.update');
    Route::post('/job-types/{id}', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'update'])
        ->name('admin.job-types.update-post');
    Route::delete('/job-types/{id}', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'destroy'])
        ->name('admin.job-types.destroy');
    Route::post('/job-types/{id}/toggle-status', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'toggleStatus'])
        ->name('admin.job-types.toggle-status');
    Route::get('/job-types/icons', [App\Http\Controllers\Job\Setting\JobTypeController::class, 'getIcons'])
        ->name('admin.job-types.icons');
});



Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Job Categories
    Route::get('/job-categories', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'index'])
        ->name('admin.job-categories');
    Route::get('/job-categories/data', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'getData'])
        ->name('admin.job-categories.data');
    Route::get('/job-categories/{id}', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'show'])
        ->name('admin.job-categories.show');
    Route::post('/job-categories', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'store'])
        ->name('admin.job-categories.store');
    Route::put('/job-categories/{id}', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'update'])
        ->name('admin.job-categories.update');
    Route::post('/job-categories/{id}', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'update'])
        ->name('admin.job-categories.update-post');
    Route::delete('/job-categories/{id}', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'destroy'])
        ->name('admin.job-categories.destroy');
    Route::post('/job-categories/{id}/toggle-status', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'toggleStatus'])
        ->name('admin.job-categories.toggle-status');
    Route::get('/job-categories/icons', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'getIcons'])
        ->name('admin.job-categories.icons');
    Route::get('/job-categories/colors', [App\Http\Controllers\Job\Setting\JobCategoryController::class, 'getColors'])
        ->name('admin.job-categories.colors');
});


Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Industries
    Route::get('/industries', [App\Http\Controllers\Job\Setting\IndustryController::class, 'index'])
        ->name('admin.industries');
    Route::get('/industries/data', [App\Http\Controllers\Job\Setting\IndustryController::class, 'getData'])
        ->name('admin.industries.data');
    Route::get('/industries/{id}', [App\Http\Controllers\Job\Setting\IndustryController::class, 'show'])
        ->name('admin.industries.show');
    Route::post('/industries', [App\Http\Controllers\Job\Setting\IndustryController::class, 'store'])
        ->name('admin.industries.store');
    Route::put('/industries/{id}', [App\Http\Controllers\Job\Setting\IndustryController::class, 'update'])
        ->name('admin.industries.update');
    Route::post('/industries/{id}', [App\Http\Controllers\Job\Setting\IndustryController::class, 'update'])
        ->name('admin.industries.update-post');
    Route::delete('/industries/{id}', [App\Http\Controllers\Job\Setting\IndustryController::class, 'destroy'])
        ->name('admin.industries.destroy');
    Route::post('/industries/{id}/toggle-status', [App\Http\Controllers\Job\Setting\IndustryController::class, 'toggleStatus'])
        ->name('admin.industries.toggle-status');
    Route::get('/industries/icons', [App\Http\Controllers\Job\Setting\IndustryController::class, 'getIcons'])
        ->name('admin.industries.icons');
});


use App\Http\Controllers\Job\JobIndex\PagesController;

// Pages Management
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Pages
    Route::get('/pages', [PagesController::class, 'index'])
        ->name('admin.pages');
    Route::get('/pages/data', [PagesController::class, 'getData'])
        ->name('admin.pages.data');
    Route::get('/pages/countries', [PagesController::class, 'getCountries'])
        ->name('admin.pages.countries');
    Route::get('/pages/{id}', [PagesController::class, 'show'])
        ->name('admin.pages.show');
    Route::post('/pages', [PagesController::class, 'store'])
        ->name('admin.pages.store');
    Route::put('/pages/{id}', [PagesController::class, 'update'])
        ->name('admin.pages.update');
    Route::post('/pages/{id}', [PagesController::class, 'update'])
        ->name('admin.pages.update-post');
    Route::delete('/pages/{id}', [PagesController::class, 'destroy'])
        ->name('admin.pages.destroy');
    Route::post('/pages/{id}/toggle-status', [PagesController::class, 'toggleStatus'])
        ->name('admin.pages.toggle-status');
});


// In routes/web.php
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Job Locations
    Route::get('/job-locations', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'index'])
        ->name('admin.job-locations');
    Route::get('/job-locations/data', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'getData'])
        ->name('admin.job-locations.data');
    Route::get('/job-locations/countries', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'getCountries'])
        ->name('admin.job-locations.countries');
    Route::get('/job-locations/{id}', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'show'])
        ->name('admin.job-locations.show');
    Route::post('/job-locations', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'store'])
        ->name('admin.job-locations.store');
    Route::put('/job-locations/{id}', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'update'])
        ->name('admin.job-locations.update');
    Route::post('/job-locations/{id}', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'update'])
        ->name('admin.job-locations.update-post');
    Route::delete('/job-locations/{id}', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'destroy'])
        ->name('admin.job-locations.destroy');
    Route::post('/job-locations/{id}/toggle-status', [App\Http\Controllers\Job\JobIndex\JobLocationController::class, 'toggleStatus'])
        ->name('admin.job-locations.toggle-status');

});


use App\Http\Controllers\Job\JobIndex\CompanyController;

// ============================================
// ADMIN ROUTES
// ============================================
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::prefix('companies')->group(function () {
        
        Route::get('/', [CompanyController::class, 'index'])
            ->name('admin.companies');
        Route::get('/data', [CompanyController::class, 'getData'])
            ->name('admin.companies.data');
        Route::get('/form-data', [CompanyController::class, 'getFormData'])
            ->name('admin.companies.form-data');
        Route::get('/{id}', [CompanyController::class, 'show'])
            ->name('admin.companies.show');
        Route::post('/', [CompanyController::class, 'store'])
            ->name('admin.companies.store');
        Route::put('/{id}', [CompanyController::class, 'update'])
            ->name('admin.companies.update');
        Route::post('/{id}', [CompanyController::class, 'update'])
            ->name('admin.companies.update-post');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])
            ->name('admin.companies.destroy');
        Route::post('/{id}/toggle-status', [CompanyController::class, 'toggleStatus'])
            ->name('admin.companies.toggle-status');
        Route::post('/{id}/toggle-verified', [CompanyController::class, 'toggleVerified'])
            ->name('admin.companies.toggle-verified');
    });
});


Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::prefix('job-posts')->group(function () {
        // ============================================
        // STATIC ROUTES (NO PARAMETERS) - MUST BE FIRST
        // ============================================
        Route::get('/', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'index'])
            ->name('admin.job-posts');
        Route::get('/data', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'getData'])
            ->name('admin.job-posts.data');
        Route::get('/form-data', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'getFormData'])
            ->name('admin.job-posts.form-data');
        
        // POSTERS - Static route, must be before /{id}
        Route::get('/posters', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'getPosters'])
            ->name('admin.job-posts.posters');
        
        // ============================================
        // DYNAMIC ROUTES (WITH PARAMETERS) - MUST BE LAST
        // ============================================
        Route::get('/{id}', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'show'])
            ->name('admin.job-posts.show');
        Route::put('/{id}', [App\Http\Controllers\Job\JobPosting\JobPostController::class, 'update'])
            ->name('admin.job-posts.update');
        Route::post('/{id}', [App\Http\Controllers\Job\JobPosting\JobPostController::class, 'update'])
            ->name('admin.job-posts.update-post');
        Route::delete('/{id}', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'destroy'])
            ->name('admin.job-posts.destroy');
        Route::post('/{id}/toggle-status', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'toggleStatus'])
            ->name('admin.job-posts.toggle-status');
        Route::post('/{id}/toggle-featured', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'toggleFeatured'])
            ->name('admin.job-posts.toggle-featured');
        Route::post('/{id}/feature', [App\Http\Controllers\Job\JobIndex\JobPostController::class, 'feature'])
            ->name('admin.job-posts.feature');
    });
});




use App\Http\Controllers\Job\JobPosting\AiJobController;



// AI Job Posting Routes
Route::prefix('admin/ai')->middleware(['auth'])->group(function () {
    Route::get('/job-posting/{country?}', function ($country = 'AU') {
        $countryNames = [
            'AU' => 'Australia',
            'UG' => 'Uganda',
            'KE' => 'Kenya',
            'TZ' => 'Tanzania',
            'RW' => 'Rwanda',
            'MW' => 'Malawi',
            'ZM' => 'Zambia',
            'SG' => 'Singapore',
        ];
        return view('job.job-posting.ai-posting', [
            'selectedCountry' => $country,
            'countryName' => $countryNames[$country] ?? 'Select Country',
        ]);
    })->name('admin.ai.job-posting');
});

// AI API Routes (for AJAX calls)
Route::prefix('ai')->middleware(['auth'])->group(function () {
    Route::get('/models', [AiJobController::class, 'getModels'])->name('ai.models');
    Route::post('/extract-job', [AiJobController::class, 'extractJobData'])->name('ai.extract-job');
    Route::post('/extract-image', [AiJobController::class, 'extractFromImage'])->name('ai.extract-image');
    Route::post('/enhance-field', [AiJobController::class, 'enhanceField'])->name('ai.enhance-field');
    Route::post('/generate-from-title', [AiJobController::class, 'generateFromTitle'])->name('ai.generate-from-title');
});


// ============================================
// AI JOB POSTING ROUTES
// ============================================
Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    // AI Job Posting - Show job data for editing
    Route::get('/ai/job-posts/{id}', [App\Http\Controllers\Job\JobPosting\JobPostController::class, 'show'])
        ->name('admin.ai.job-posts.show');
    
    // AI Job Posting - Store new job posts
    Route::post('/ai/job-posts/store', [App\Http\Controllers\Job\JobPosting\JobPostController::class, 'store'])
        ->name('admin.ai.job-posts.store');

    
});



use App\Http\Controllers\Job\JobIndex\SitemapController;

// Sitemap Routes
Route::prefix('admin/sitemap')->name('admin.sitemap.')->middleware(['auth'])->group(function () {
    Route::get('/', [SitemapController::class, 'dashboard'])->name('dashboard');
    Route::get('/statistics', [SitemapController::class, 'getStatistics'])->name('statistics');
    Route::get('/jobs', [SitemapController::class, 'getJobs'])->name('jobs');
    Route::post('/ping', [SitemapController::class, 'pingJobs'])->name('ping');
    Route::post('/mark-indexed', [SitemapController::class, 'markIndexed'])->name('mark-indexed');
    Route::get('/generate', [SitemapController::class, 'generate'])->name('generate');
    Route::get('/stats', [SitemapController::class, 'stats'])->name('stats');
});



use App\Http\Controllers\Job\JobIndex\SocialMediaPlatformController;

// Social Media Platforms Management
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Main view
    Route::get('/social-media', [SocialMediaPlatformController::class, 'index'])
        ->name('admin.social-media');
    
    // Data endpoints
    Route::get('/social-media/data', [SocialMediaPlatformController::class, 'getData'])
        ->name('admin.social-media.data');
    Route::get('/social-media/stats', [SocialMediaPlatformController::class, 'getStats'])
        ->name('admin.social-media.stats');
    Route::get('/social-media/{id}/details', [SocialMediaPlatformController::class, 'getPlatformDetails'])
        ->name('admin.social-media.details');
    
    // Dropdown data
    Route::get('/social-media/countries', [SocialMediaPlatformController::class, 'getCountries'])
        ->name('admin.social-media.countries');
    Route::get('/social-media/platforms', [SocialMediaPlatformController::class, 'getPlatforms'])
        ->name('admin.social-media.platforms');
    
    // CRUD operations
    Route::get('/social-media/{id}', [SocialMediaPlatformController::class, 'show'])
        ->name('admin.social-media.show');
    Route::post('/social-media', [SocialMediaPlatformController::class, 'store'])
        ->name('admin.social-media.store');
    Route::put('/social-media/{id}', [SocialMediaPlatformController::class, 'update'])
        ->name('admin.social-media.update');
    Route::post('/social-media/{id}', [SocialMediaPlatformController::class, 'update'])
        ->name('admin.social-media.update-post');
    Route::delete('/social-media/{id}', [SocialMediaPlatformController::class, 'destroy'])
        ->name('admin.social-media.destroy');
    
    // Toggle operations
    Route::post('/social-media/{id}/toggle-status', [SocialMediaPlatformController::class, 'toggleStatus'])
        ->name('admin.social-media.toggle-status');
    Route::post('/social-media/{id}/toggle-verified', [SocialMediaPlatformController::class, 'toggleVerified'])
        ->name('admin.social-media.toggle-verified');
    Route::post('/social-media/{id}/toggle-featured', [SocialMediaPlatformController::class, 'toggleFeatured'])
        ->name('admin.social-media.toggle-featured');
    
    // Update followers
    Route::post('/social-media/{id}/update-followers', [SocialMediaPlatformController::class, 'updateFollowers'])
        ->name('admin.social-media.update-followers');
});
<?php
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Reports\{ ExpenseReportController, JobsReportsController };

// Expense Reports Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/expense-reports', [ExpenseReportController::class, 'index'])->name('admin.expense-reports');
    Route::get('/expense-reports/summary', [ExpenseReportController::class, 'summary'])->name('admin.expense-reports.summary');
    Route::get('/expense-reports/category', [ExpenseReportController::class, 'byCategory'])->name('admin.expense-reports.category');
    Route::get('/expense-reports/vendor', [ExpenseReportController::class, 'byVendor'])->name('admin.expense-reports.vendor');
    Route::get('/expense-reports/employee', [ExpenseReportController::class, 'byEmployee'])->name('admin.expense-reports.employee');
    Route::get('/expense-reports/payment-method', [ExpenseReportController::class, 'byPaymentMethod'])->name('admin.expense-reports.payment-method');
    Route::get('/expense-reports/trends', [ExpenseReportController::class, 'trends'])->name('admin.expense-reports.trends');
    Route::get('/expense-reports/recurring', [ExpenseReportController::class, 'recurring'])->name('admin.expense-reports.recurring');
    Route::get('/expense-reports/tax', [ExpenseReportController::class, 'taxReport'])->name('admin.expense-reports.tax');
    Route::get('/expense-reports/budget', [ExpenseReportController::class, 'budgetVsActual'])->name('admin.expense-reports.budget');
    Route::get('/expense-reports/audit', [ExpenseReportController::class, 'audit'])->name('admin.expense-reports.audit');
    Route::get('/expense-reports/export/{type}', [ExpenseReportController::class, 'export'])->name('admin.expense-reports.export');
});




// Jobs Reports Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    // Jobs Reports
    Route::get('/jobs-reports', [JobsReportsController::class, 'index'])->name('admin.jobs-reports');
    Route::get('/jobs-reports/summary', [JobsReportsController::class, 'summary'])->name('admin.jobs-reports.summary');
    Route::get('/jobs-reports/category', [JobsReportsController::class, 'byCategory'])->name('admin.jobs-reports.category');
    Route::get('/jobs-reports/company', [JobsReportsController::class, 'byCompany'])->name('admin.jobs-reports.company');
    Route::get('/jobs-reports/location', [JobsReportsController::class, 'byLocation'])->name('admin.jobs-reports.location');
    Route::get('/jobs-reports/source', [JobsReportsController::class, 'bySource'])->name('admin.jobs-reports.source');
    Route::get('/jobs-reports/performance', [JobsReportsController::class, 'performance'])->name('admin.jobs-reports.performance');
    Route::get('/jobs-reports/seo', [JobsReportsController::class, 'seo'])->name('admin.jobs-reports.seo');
    Route::get('/jobs-reports/trends', [JobsReportsController::class, 'trends'])->name('admin.jobs-reports.trends');
    Route::get('/jobs-reports/poster', [JobsReportsController::class, 'byPoster'])->name('admin.jobs-reports.poster');
    Route::get('/jobs-reports/country', [JobsReportsController::class, 'byCountry'])->name('admin.jobs-reports.country');
    Route::get('/jobs-reports/export/{type}', [JobsReportsController::class, 'export'])->name('admin.jobs-reports.export');
    Route::get('/jobs-reports/timeline', [JobsReportsController::class, 'byTimeline'])->name('admin.jobs-reports.timeline');
    Route::get('/jobs-reports/poster-activity', [JobsReportsController::class, 'getPosterActivity'])->name('admin.jobs-reports.poster-activity');
});
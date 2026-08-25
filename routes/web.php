<?php

use App\Http\Controllers\{ ProfileController };
use App\Http\Controllers\Settings\{ArtisanCommandController };
use App\Http\Controllers\Admin\{ DashboardController, DepartmentController };
use Illuminate\Support\Facades\Route;


Route::get('/', [DashboardController::class, 'index'])->name('login');


// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/artisan',       [ArtisanCommandController::class, 'index'])->name('artisan.index');
    Route::post('/artisan/run',  [ArtisanCommandController::class, 'run'])->name('artisan.run');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
});




// Finances
use App\Http\Controllers\Finance\{ PaymentMethodController, CurrencyController, 
    DepositController, PaymentSourceController, PaymentPurposeController
    };

// Currency Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('admin.currencies');
    Route::get('/currencies/data', [CurrencyController::class, 'getCurrencies'])->name('admin.currencies.data');
    Route::get('/currencies/{id}', [CurrencyController::class, 'getCurrency'])->name('admin.currencies.get');
    Route::post('/currencies', [CurrencyController::class, 'storeCurrency'])->name('admin.currencies.store');
    Route::put('/currencies/{id}', [CurrencyController::class, 'updateCurrency'])->name('admin.currencies.update');
    Route::delete('/currencies/{id}', [CurrencyController::class, 'deleteCurrency'])->name('admin.currencies.delete');
    Route::patch('/currencies/{id}/toggle-status', [CurrencyController::class, 'toggleCurrencyStatus'])->name('admin.currencies.toggle-status');
    
    // Payment Method Routes
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('admin.payment-methods');
    Route::get('/payment-methods/data', [PaymentMethodController::class, 'getPaymentMethods'])->name('admin.payment-methods.data');
    Route::get('/payment-methods/currencies/all', [PaymentMethodController::class, 'getCurrencies'])->name('admin.payment-methods.currencies');
    Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'getPaymentMethod'])->name('admin.payment-methods.get');
    Route::post('/payment-methods', [PaymentMethodController::class, 'storePaymentMethod'])->name('admin.payment-methods.store');
    Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'updatePaymentMethod'])->name('admin.payment-methods.update');
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'deletePaymentMethod'])->name('admin.payment-methods.delete');
    Route::patch('/payment-methods/{id}/toggle-status', [PaymentMethodController::class, 'togglePaymentMethodStatus'])->name('admin.payment-methods.toggle-status');
    Route::post('/payment-methods/transfer', [PaymentMethodController::class, 'transferBetweenMethods'])->name('admin.payment-methods.transfer');});



// Finance Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    // Deposits - SPECIFIC ROUTES FIRST
    Route::get('/deposits', [DepositController::class, 'deposits'])->name('admin.deposits');
    Route::get('/deposits/data', [DepositController::class, 'getDeposits'])->name('admin.deposits.data');
    Route::get('/deposits/payment-methods', [DepositController::class, 'getPaymentMethods'])->name('admin.deposits.payment-methods');
    Route::get('/deposits/currencies', [DepositController::class, 'getCurrencies'])->name('admin.deposits.currencies');
    Route::get('/deposits/departments', [DepositController::class, 'getDepartments'])->name('admin.deposits.departments');  // NEW
    Route::get('/deposits/users', [DepositController::class, 'getUsers'])->name('admin.deposits.users');                    // NEW
    Route::get('/deposits/sources', [DepositController::class, 'getSources'])->name('admin.deposits.sources');
    Route::get('/deposits/purposes', [DepositController::class, 'getPurposes'])->name('admin.deposits.purposes');
    Route::post('/deposits', [DepositController::class, 'storeDeposit'])->name('admin.deposits.store');
    
    // Deposits - DYNAMIC ROUTES LAST
    Route::get('/deposits/{id}', [DepositController::class, 'getDeposit'])->name('admin.deposits.get');
    Route::post('/deposits/{id}/approve', [DepositController::class, 'approveDeposit'])->name('admin.deposits.approve');
    Route::post('/deposits/{id}/cancel', [DepositController::class, 'cancelDeposit'])->name('admin.deposits.cancel');
    Route::delete('/deposits/{id}', [DepositController::class, 'deleteDeposit'])->name('admin.deposits.delete');

    Route::get('/deposits/{id}/receipts', [DepositController::class, 'getReceipts'])->name('admin.deposits.receipts');
    Route::post('/deposits/{id}/receipts', [DepositController::class, 'uploadReceipt'])->name('admin.deposits.upload-receipt');
    Route::delete('/deposits/{depositId}/receipts/{receiptId}', [DepositController::class, 'deleteReceipt'])->name('admin.deposits.delete-receipt');
    Route::post('/deposits/{depositId}/receipts/{receiptId}/primary', [DepositController::class, 'setPrimaryReceipt'])->name('admin.deposits.set-primary-receipt');
});

// Payment Sources Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/payment-sources', [PaymentSourceController::class, 'index'])->name('admin.payment-sources');
    Route::get('/payment-sources/data', [PaymentSourceController::class, 'getData'])->name('admin.payment-sources.data');
    Route::get('/payment-sources/{id}', [PaymentSourceController::class, 'show'])->name('admin.payment-sources.show');
    Route::post('/payment-sources', [PaymentSourceController::class, 'store'])->name('admin.payment-sources.store');
    Route::put('/payment-sources/{id}', [PaymentSourceController::class, 'update'])->name('admin.payment-sources.update');
    Route::delete('/payment-sources/{id}', [PaymentSourceController::class, 'destroy'])->name('admin.payment-sources.destroy');
});

// Payment Purposes Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/payment-purposes', [PaymentPurposeController::class, 'index'])->name('admin.payment-purposes');
    Route::get('/payment-purposes/data', [PaymentPurposeController::class, 'getData'])->name('admin.payment-purposes.data');
    Route::get('/payment-purposes/{id}', [PaymentPurposeController::class, 'show'])->name('admin.payment-purposes.show');
    Route::post('/payment-purposes', [PaymentPurposeController::class, 'store'])->name('admin.payment-purposes.store');
    Route::put('/payment-purposes/{id}', [PaymentPurposeController::class, 'update'])->name('admin.payment-purposes.update');
    Route::delete('/payment-purposes/{id}', [PaymentPurposeController::class, 'destroy'])->name('admin.payment-purposes.destroy');
});


use App\Http\Controllers\Finance\FinancialReportController;

// Financial Reports Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    // Main dashboard
    Route::get('/accounting/payment-methods', [FinancialReportController::class, 'paymentMethods'])->name('accounting.payment-methods.index');
    Route::get('/accounting/account-balances', [FinancialReportController::class, 'accountBalances'])->name('accounting.account-balances');
    Route::get('/accounting/transaction-ledger', [FinancialReportController::class, 'transactionLedger'])->name('accounting.transaction-ledger');
    Route::get('/accounting/transaction-details/{id}', [FinancialReportController::class, 'getTransactionDetails'])->name('accounting.transaction-details');
    Route::get('/accounting/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('accounting.income-statement');
    Route::get('/accounting/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('accounting.cash-flow');
    Route::get('/accounting/flexible-report', [FinancialReportController::class, 'flexibleReport'])->name('accounting.flexible-report');
});




use App\Http\Controllers\Expense\ExpenseCategoryController;
use App\Http\Controllers\Expense\ExpenseController;

// Expense Categories Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('admin.expense-categories');
    Route::get('/expense-categories/data', [ExpenseCategoryController::class, 'getCategories'])->name('admin.expense-categories.data');
    Route::get('/expense-categories/all', [ExpenseCategoryController::class, 'getAll'])->name('admin.expense-categories.all');
    Route::get('/expense-categories/{id}', [ExpenseCategoryController::class, 'show'])->name('admin.expense-categories.show');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('admin.expense-categories.store');
    Route::put('/expense-categories/{id}', [ExpenseCategoryController::class, 'update'])->name('admin.expense-categories.update');
    Route::delete('/expense-categories/{id}', [ExpenseCategoryController::class, 'destroy'])->name('admin.expense-categories.destroy');
    Route::post('/expense-categories/{id}/toggle-status', [ExpenseCategoryController::class, 'toggleStatus'])->name('admin.expense-categories.toggle-status');
});


// Expenses Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('admin.expenses');
    Route::get('/expenses/data', [ExpenseController::class, 'getExpenses'])->name('admin.expenses.data');
    Route::get('/expenses/form-data', [ExpenseController::class, 'getFormData'])->name('admin.expenses.form-data');
    Route::get('/expenses/{id}', [ExpenseController::class, 'show'])->name('admin.expenses.show');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
    Route::put('/expenses/{id}', [ExpenseController::class, 'update'])->name('admin.expenses.update');
    Route::post('/expenses/{id}/approve', [ExpenseController::class, 'approve'])->name('admin.expenses.approve');
    Route::post('/expenses/{id}/pay', [ExpenseController::class, 'pay'])->name('admin.expenses.pay');
    Route::post('/expenses/{id}/cancel', [ExpenseController::class, 'cancel'])->name('admin.expenses.cancel');
    Route::post('/expenses/{id}/reject', [ExpenseController::class, 'reject'])->name('admin.expenses.reject');
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');
});



use App\Http\Controllers\Compensation\{ EmployeeController, SalaryStructureController, EmployeePaymentController, BonusController,
    PhantomEquityController, ProfitShareDistributionController, PerformanceReviewController, DepartmentProfitShareController };

Route::middleware('auth')->prefix('admin')->group(function () {
    // Employee Management Routes
    Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employees');
    Route::get('/employees/data', [EmployeeController::class, 'getEmployees'])->name('admin.employees.data');
    Route::get('/employees/form-data', [EmployeeController::class, 'getFormData'])->name('admin.employees.form-data');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('admin.employees.show');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
    Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('admin.employees.update');
    
    // Status toggle - use PUT instead of PATCH
    Route::put('/employees/{id}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('admin.employees.toggle-status');

    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');
    Route::post('/employees/sync', [EmployeeController::class, 'syncWithUsers'])->name('admin.employees.sync');
});

// Salary Structure Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/salary-structures', [SalaryStructureController::class, 'index'])->name('admin.salary-structures');
    Route::get('/salary-structures/data', [SalaryStructureController::class, 'getSalaryStructures'])->name('admin.salary-structures.data');
    Route::get('/salary-structures/form-data', [SalaryStructureController::class, 'getFormData'])->name('admin.salary-structures.form-data');
    Route::get('/salary-structures/{id}', [SalaryStructureController::class, 'show'])->name('admin.salary-structures.show');
    Route::post('/salary-structures', [SalaryStructureController::class, 'store'])->name('admin.salary-structures.store');
    Route::post('/salary-structures/{id}', [SalaryStructureController::class, 'update'])->name('admin.salary-structures.update');
    Route::post('/salary-structures/{id}/toggle-status', [SalaryStructureController::class, 'toggleStatus'])->name('admin.salary-structures.toggle-status');
    Route::delete('/salary-structures/{id}', [SalaryStructureController::class, 'destroy'])->name('admin.salary-structures.destroy');
});


// Employee Payment Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/employee-payments', [EmployeePaymentController::class, 'index'])->name('admin.employee-payments');
    Route::get('/employee-payments/data', [EmployeePaymentController::class, 'getPayments'])->name('admin.employee-payments.data');
    Route::get('/employee-payments/form-data', [EmployeePaymentController::class, 'getFormData'])->name('admin.employee-payments.form-data');
    
    // IMPORTANT: Generate route MUST come BEFORE the {id} routes
    Route::post('/employee-payments/generate', [EmployeePaymentController::class, 'generateSalaryPayments'])->name('admin.employee-payments.generate');
    
    // These {id} routes come AFTER the generate route
    Route::get('/employee-payments/{id}', [EmployeePaymentController::class, 'show'])->name('admin.employee-payments.show');
    Route::post('/employee-payments', [EmployeePaymentController::class, 'store'])->name('admin.employee-payments.store');
    Route::post('/employee-payments/{id}', [EmployeePaymentController::class, 'update'])->name('admin.employee-payments.update');
    Route::post('/employee-payments/{id}/approve', [EmployeePaymentController::class, 'approve'])->name('admin.employee-payments.approve');
    Route::post('/employee-payments/{id}/pay', [EmployeePaymentController::class, 'pay'])->name('admin.employee-payments.pay');
    Route::post('/employee-payments/{id}/cancel', [EmployeePaymentController::class, 'cancel'])->name('admin.employee-payments.cancel');
    Route::post('/employee-payments/{id}/reject', [EmployeePaymentController::class, 'reject'])->name('admin.employee-payments.reject');
    Route::delete('/employee-payments/{id}', [EmployeePaymentController::class, 'destroy'])->name('admin.employee-payments.destroy');
});


// Department Profit Share Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/department-profit-share', [DepartmentProfitShareController::class, 'index'])->name('admin.department-profit-share');
    Route::get('/department-profit-share/data', [DepartmentProfitShareController::class, 'getPeriods'])->name('admin.department-profit-share.data');
    Route::get('/department-profit-share/form-data', [DepartmentProfitShareController::class, 'getFormData'])->name('admin.department-profit-share.form-data');
    Route::get('/department-profit-share/{id}', [DepartmentProfitShareController::class, 'show'])->name('admin.department-profit-share.show');
    Route::post('/department-profit-share/calculate', [DepartmentProfitShareController::class, 'calculate'])->name('admin.department-profit-share.calculate');
    Route::post('/department-profit-share/{id}', [DepartmentProfitShareController::class, 'update'])->name('admin.department-profit-share.update');
    Route::delete('/department-profit-share/{id}', [DepartmentProfitShareController::class, 'destroy'])->name('admin.department-profit-share.destroy');
});


// Phantom Equity Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/phantom-equity', [PhantomEquityController::class, 'index'])->name('admin.phantom-equity');
    Route::get('/phantom-equity/data', [PhantomEquityController::class, 'getTransactions'])->name('admin.phantom-equity.data');
    Route::get('/phantom-equity/form-data', [PhantomEquityController::class, 'getFormData'])->name('admin.phantom-equity.form-data');
    Route::get('/phantom-equity/{id}', [PhantomEquityController::class, 'show'])->name('admin.phantom-equity.show');
    Route::get('/phantom-equity/user/{userId}', [PhantomEquityController::class, 'getUserSummary'])->name('admin.phantom-equity.user-summary');
    Route::post('/phantom-equity', [PhantomEquityController::class, 'store'])->name('admin.phantom-equity.store');
    Route::post('/phantom-equity/{id}', [PhantomEquityController::class, 'update'])->name('admin.phantom-equity.update');
    Route::delete('/phantom-equity/{id}', [PhantomEquityController::class, 'destroy'])->name('admin.phantom-equity.destroy');
});


// Profit Share Distribution Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/profit-share', [ProfitShareDistributionController::class, 'index'])->name('admin.profit-share');
    Route::get('/profit-share/data', [ProfitShareDistributionController::class, 'getDistributions'])->name('admin.profit-share.data');
    Route::get('/profit-share/form-data', [ProfitShareDistributionController::class, 'getFormData'])->name('admin.profit-share.form-data');
    Route::get('/profit-share/{id}', [ProfitShareDistributionController::class, 'show'])->name('admin.profit-share.show');
    Route::get('/profit-share/period/{periodId}/balance', [ProfitShareDistributionController::class, 'getAvailableBalance'])->name('admin.profit-share.balance');
    Route::get('/profit-share/department/{departmentId}/employees', [ProfitShareDistributionController::class, 'getDepartmentEmployees'])->name('admin.profit-share.department-employees');
    Route::get('/profit-share/{id}/payment-data', [ProfitShareDistributionController::class, 'getMarkAsPaidData'])->name('admin.profit-share.payment-data');
    Route::post('/profit-share', [ProfitShareDistributionController::class, 'store'])->name('admin.profit-share.store');
    Route::post('/profit-share/bulk', [ProfitShareDistributionController::class, 'bulkDistribute'])->name('admin.profit-share.bulk');
    Route::post('/profit-share/{id}', [ProfitShareDistributionController::class, 'update'])->name('admin.profit-share.update');
    Route::post('/profit-share/{id}/mark-paid', [ProfitShareDistributionController::class, 'markAsPaid'])->name('admin.profit-share.mark-paid');
    Route::delete('/profit-share/{id}', [ProfitShareDistributionController::class, 'destroy'])->name('admin.profit-share.destroy');
});

// Performance Review Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/performance-reviews', [PerformanceReviewController::class, 'index'])->name('admin.performance-reviews');
    Route::get('/performance-reviews/data', [PerformanceReviewController::class, 'getReviews'])->name('admin.performance-reviews.data');
    Route::get('/performance-reviews/form-data', [PerformanceReviewController::class, 'getFormData'])->name('admin.performance-reviews.form-data');
    Route::get('/performance-reviews/{id}', [PerformanceReviewController::class, 'show'])->name('admin.performance-reviews.show');
    Route::post('/performance-reviews', [PerformanceReviewController::class, 'store'])->name('admin.performance-reviews.store');
    Route::post('/performance-reviews/{id}', [PerformanceReviewController::class, 'update'])->name('admin.performance-reviews.update');
    Route::post('/performance-reviews/{id}/approve', [PerformanceReviewController::class, 'approve'])->name('admin.performance-reviews.approve');
    Route::delete('/performance-reviews/{id}', [PerformanceReviewController::class, 'destroy'])->name('admin.performance-reviews.destroy');
});


// Bonus Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/bonuses', [BonusController::class, 'index'])->name('admin.bonuses');
    Route::get('/bonuses/data', [BonusController::class, 'getBonuses'])->name('admin.bonuses.data');
    Route::get('/bonuses/form-data', [BonusController::class, 'getFormData'])->name('admin.bonuses.form-data');
    Route::get('/bonuses/{id}', [BonusController::class, 'show'])->name('admin.bonuses.show');
    Route::post('/bonuses', [BonusController::class, 'store'])->name('admin.bonuses.store');
    Route::post('/bonuses/{id}', [BonusController::class, 'update'])->name('admin.bonuses.update');
    Route::post('/bonuses/{id}/approve', [BonusController::class, 'approve'])->name('admin.bonuses.approve');
    Route::post('/bonuses/{id}/pay', [BonusController::class, 'pay'])->name('admin.bonuses.pay');
    Route::delete('/bonuses/{id}', [BonusController::class, 'destroy'])->name('admin.bonuses.destroy');
});




require __DIR__.'/auth.php';
require __DIR__.'/report.php';
require __DIR__.'/user.php';
require __DIR__.'/migration.php';

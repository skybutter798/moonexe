<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OrderController;

use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\PairController;

require_once 'theme-routes.php';

Auth::routes();

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
         ->name('admin.dashboard');

    // User Management (Admin Work)
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users/{id}/disable', [UserController::class, 'disable'])->name('admin.users.disable');
    Route::post('/users/{id}/enable', [UserController::class, 'enable'])->name('admin.users.enable');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');

    // Package Management (Admin Work)
    Route::prefix('packages')->name('admin.packages.')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::post('/', [PackageController::class, 'store'])->name('store');
        Route::post('/{id}/disable', [PackageController::class, 'disable'])->name('disable');
        Route::post('/{id}/enable', [PackageController::class, 'enable'])->name('enable');
        Route::put('/{id}', [PackageController::class, 'update'])->name('update');
    });

    // Deposit, Withdrawal, Currency, Pair, etc.
    Route::get('/deposits', [AdminDepositController::class, 'index'])->name('admin.deposits.index');
    Route::post('/deposits/{id}/approve', [AdminDepositController::class, 'approve'])->name('admin.deposits.approve');
    Route::post('/deposits/{id}/reject', [AdminDepositController::class, 'reject'])->name('admin.deposits.reject');

    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
    Route::post('/withdrawals/{id}/approve', [AdminWithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [AdminWithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');

    Route::get('/currencies', [CurrencyController::class, 'index'])->name('admin.currencies.index');
    Route::get('/currencies/create', [CurrencyController::class, 'create'])->name('admin.currencies.create');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('admin.currencies.store');

    Route::get('/pairs', [PairController::class, 'index'])->name('admin.pairs.index');
    Route::get('/pairs/create', [PairController::class, 'create'])->name('admin.pairs.create');
    Route::post('/pairs', [PairController::class, 'store'])->name('admin.pairs.store');
});

// For regular users (with custom middleware to ensure non-admin)
Route::prefix('user-dashboard')->middleware(['auth', 'user.only'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/assets', [AssetsController::class, 'index'])->name('user.assets');
    Route::post('/deposit', [AssetsController::class, 'deposit'])->name('user.deposit');
    Route::post('/withdrawal', [AssetsController::class, 'withdrawal'])->name('user.withdrawal');
    Route::post('/transfer', [AssetsController::class, 'transfer'])->name('user.transfer');
    Route::post('/user/package', [AssetsController::class, 'buyPackage'])->name('user.buyPackage');
    Route::post('/transfer/trading', [AssetsController::class, 'transferTrading'])->name('user.tradingTransfer');
    Route::get('/referral', [ReferralController::class, 'index'])->name('user.referral');
    Route::get('/account', [AccountController::class, 'index'])->name('user.account');
    Route::get('/order', [OrderController::class, 'index'])->name('user.order');
    Route::post('/order', [OrderController::class, 'store'])->name('user.order.store');
    Route::post('/order/claim', [OrderController::class, 'claim'])->name('user.order.claim');
    Route::post('/send', [AssetsController::class, 'sendFunds'])->name('user.sendFunds');
    Route::post('/account/change-password', [AccountController::class, 'changePassword'])->name('user.changePassword');
    Route::put('/account/update', [AccountController::class, 'updateProfile'])->name('user.updateProfile');
    Route::post('/apply-promotion', [DashboardController::class, 'applyPromotion'])->name('user.applyPromotion');
    
    //New layout
    Route::get('/account_v2', [AccountController::class, 'index'])->name('user.account_v2');
    Route::get('/assets_v2', [AssetsController::class, 'index'])->name('user.assets_v2');
    Route::get('/dashboard_v2', [DashboardController::class, 'index'])->name('user.dashboard_v2');
    Route::get('/referral_v2', [ReferralController::class, 'index'])->name('user.referral_v2');
    Route::get('/order_v2', [OrderController::class, 'index'])->name('user.order_v2');

});

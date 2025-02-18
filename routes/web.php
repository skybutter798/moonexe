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

// Dashboard Route
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/disable', [UserController::class, 'disable'])->name('users.disable');
    Route::post('/users/{id}/enable', [UserController::class, 'enable'])->name('users.enable');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
});

Route::middleware(['auth'])->prefix('packages')->name('packages.')->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('index');
    Route::post('/', [PackageController::class, 'store'])->name('store');
    Route::post('/{id}/disable', [PackageController::class, 'disable'])->name('disable');
    Route::post('/{id}/enable', [PackageController::class, 'enable'])->name('enable');
    Route::put('/{id}', [PackageController::class, 'update'])->name('update');
});

Route::prefix('user-dashboard')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'userDashboard'])
        ->name('user.dashboard');

    Route::get('/assets', [AssetsController::class, 'index'])
        ->name('user.assets');

    Route::post('/deposit', [AssetsController::class, 'deposit'])
        ->name('user.deposit');

    Route::post('/withdrawal', [AssetsController::class, 'withdrawal'])
        ->name('user.withdrawal');

    // New route for transfers
    Route::post('/transfer', [AssetsController::class, 'transfer'])
        ->name('user.transfer');

    Route::get('/referral', [ReferralController::class, 'index'])
        ->name('user.referral');

    Route::get('/account', [AccountController::class, 'index'])
        ->name('user.account');

    Route::get('/order', [OrderController::class, 'index'])
        ->name('user.order');
        
    Route::post('/order', [OrderController::class, 'store'])->name('user.order.store');

});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Deposit routes
    Route::get('/deposits', [AdminDepositController::class, 'index'])->name('admin.deposits.index');
    Route::post('/deposits/{id}/approve', [AdminDepositController::class, 'approve'])->name('admin.deposits.approve');
    Route::post('/deposits/{id}/reject', [AdminDepositController::class, 'reject'])->name('admin.deposits.reject');

    // Withdrawal routes
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
    Route::post('/withdrawals/{id}/approve', [AdminWithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [AdminWithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');
    
    // Currency routes.
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('admin.currencies.index');
    Route::get('/currencies/create', [CurrencyController::class, 'create'])->name('admin.currencies.create');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('admin.currencies.store');

    // Pair routes.
    Route::get('/pairs', [PairController::class, 'index'])->name('admin.pairs.index');
    Route::get('/pairs/create', [PairController::class, 'create'])->name('admin.pairs.create');
    Route::post('/pairs', [PairController::class, 'store'])->name('admin.pairs.store');
});
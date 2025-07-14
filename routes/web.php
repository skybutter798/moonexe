<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController as UserDashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DirectRangeController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AnnoucementController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\AuthController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\PairController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AdminToolController;

require_once 'theme-routes.php';

Auth::routes();

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // User Management (Admin Work)
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users/{id}/disable', [UserController::class, 'disable'])->name('admin.users.disable');
    Route::post('/users/{id}/enable', [UserController::class, 'enable'])->name('admin.users.enable');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::get('/users/{id}/wallet-breakdown', [UserController::class, 'walletBreakdown']);

    // DirectRange Management
    Route::prefix('directranges')
         ->name('admin.directranges.')
         ->group(function () {
             Route::get('/', [DirectRangeController::class, 'index'])
                  ->name('index');
             Route::post('/', [DirectRangeController::class, 'store'])
                  ->name('store');
             Route::put('{id}', [DirectRangeController::class, 'update'])
                  ->name('update');
             Route::delete('{id}', [DirectRangeController::class, 'destroy'])
                  ->name('destroy');
         });
    
    Route::post('/matchingranges', [\App\Http\Controllers\MatchingRangeController::class, 'store'])
         ->name('admin.matchingranges.store');

    Route::put('/matchingranges/{id}', [\App\Http\Controllers\MatchingRangeController::class, 'update'])
         ->name('admin.matchingranges.update');

    Route::delete('/matchingranges/{id}', [\App\Http\Controllers\MatchingRangeController::class, 'destroy'])
         ->name('admin.matchingranges.destroy');

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
    Route::patch('/currencies/{id}/toggle', [CurrencyController::class, 'toggleStatus']) ->name('admin.currencies.toggle');

    Route::get('/pairs', [PairController::class, 'index'])->name('admin.pairs.index');
    Route::get('/pairs/create', [PairController::class, 'create'])->name('admin.pairs.create');
    Route::post('/pairs', [PairController::class, 'store'])->name('admin.pairs.store');
    Route::get('pairs/{pair}/edit', [PairController::class, 'edit']) ->name('admin.pairs.edit');
    Route::patch('pairs/{pair}', [PairController::class, 'update']) ->name('admin.pairs.update');
    Route::patch('pairs/{pair}/disable', [PairController::class, 'disable']) ->name('admin.pairs.disable');
    
    // Wallet Management
    Route::get('/wallets', [WalletController::class, 'index'])->name('admin.wallets.index');
    Route::get('/wallets/{user}/edit',[WalletController::class, 'edit'])->name('admin.wallets.edit');
    Route::put('/wallets/{user}', [WalletController::class, 'update'])->name('admin.wallets.update');
 
    Route::get('referrals', [AdminReferralController::class, 'index']) ->name('admin.referrals.index');
    
    Route::get('annoucement', [AnnoucementController::class, 'index']) ->name('admin.annoucement.index');
    Route::post('annoucement', [AnnoucementController::class, 'store']) ->name('admin.annoucement.store');
    Route::get('annoucement/{annoucement}/edit', [AnnoucementController::class, 'edit']) ->name('admin.annoucement.edit');
    Route::patch('annoucement/{annoucement}', [AnnoucementController::class, 'update']) ->name('admin.annoucement.update');
    
    // Show the form
    Route::get('/walletreport', [AdminToolController::class, 'index'])->name('admin.tools.index');
    Route::post('/walletreport', [AdminToolController::class, 'walletReport'])->name('admin.tools.walletReport');
    Route::post('/walletbreakdown', [AdminToolController::class, 'realWalletBreakdown'])->name('admin.tools.realWalletBreakdown');
    Route::post('/walletflow', [AdminToolController::class, 'walletFlowReport'])->name('admin.tools.walletFlowReport');
    
    Route::get('/impersonate/{id}', [UserController::class, 'impersonate'])->name('admin.users.impersonate');
});

Route::get('/admin/impersonate-leave', [UserController::class, 'leaveImpersonation'])->name('admin.users.leave');

// For regular users (with custom middleware to ensure non-admin)
Route::prefix('user-dashboard')->middleware(['auth', 'user.only'])->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
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
    Route::post('/apply-promotion', [UserDashboardController::class, 'applyPromotion'])->name('user.applyPromotion');
    
    Route::post('/bonusclaim', [AssetsController::class, 'claimCampaignBonus'])->name('user.claimCampaignBonus');
    
    //New layout
    Route::get('/account_v2', [AccountController::class, 'index'])->name('user.account_v2');
    Route::get('/assets_v2', [AssetsController::class, 'index'])->name('user.assets_v2');
    Route::get('/dashboard_v2', [UserDashboardController::class, 'index'])->name('user.dashboard_v2');
    Route::get('/referral_v2', [ReferralController::class, 'index'])->name('user.referral_v2');
    Route::get('/order_v2', [OrderController::class, 'index'])->name('user.order_v2');
    Route::get('/annoucement', [UserDashboardController::class, 'showAnnouncements']) ->name('user.annoucement');
    Route::get('/faq', [FaqController::class, 'faq']) ->name('user.faq');
    Route::post('/contact-support', [UserController::class, 'contactSupport'])->name('user.contact.support');


});

Route::post('/generate-wallet-address', [\App\Http\Controllers\UserController::class, 'generateWalletAddress'])->name('user.generateWalletAddress');

Route::match(['get', 'post'], '/tool', [ToolController::class, 'index'])->name('tool.index');
Route::get('/tool/logout', function () { session()->forget('tool_authenticated'); return redirect()->route('tool.index'); })->name('tool.logout');
Route::post('/tool/update', [ToolController::class, 'update'])->name('tool.update');

Route::post('/send-verification-email', [VerificationController::class, 'sendEmail'])->name('user.sendVerificationEmail');
Route::get('/verify-account/{token}', [VerificationController::class, 'verify'])->name('user.verifyAccount');

Route::post('/2fa/verify', [AuthController::class, 'verify2FA'])->name('user.verify2fa');
Route::post('/2fa/disable', [AuthController::class, 'disable2FA'])->name('user.disable2fa');
Route::put('/user/security-pass', [AccountController::class, 'changeSecurityPassword'])->name('user.changeSecurityPassword');

Route::get('/pair/{pair}/volumes', [App\Http\Controllers\OrderController::class, 'volumes']);

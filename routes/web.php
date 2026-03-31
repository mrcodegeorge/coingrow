<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MainAccountController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PaymentSplitController;
use App\Http\Controllers\SubAccountController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('/main-account/deposit', [MainAccountController::class, 'deposit'])->name('main-account.deposit');
    Route::post('/main-account/withdraw', [MainAccountController::class, 'withdraw'])->name('main-account.withdraw');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/automation/auto-savings-rules', [AutomationController::class, 'storeAutoSavingsRule'])->name('automation.rules.store');
    Route::delete('/automation/auto-savings-rules/{rule}', [AutomationController::class, 'destroyAutoSavingsRule'])->name('automation.rules.destroy');
    Route::post('/automation/scheduled-transactions', [AutomationController::class, 'storeScheduledTransaction'])->name('automation.scheduled.store');
    Route::delete('/automation/scheduled-transactions/{scheduledTransaction}', [AutomationController::class, 'destroyScheduledTransaction'])->name('automation.scheduled.destroy');

    Route::post('/sub-accounts', [SubAccountController::class, 'store'])->name('sub-accounts.store');
    Route::post('/sub-accounts/{subAccount}/deposit', [SubAccountController::class, 'deposit'])->name('sub-accounts.deposit');
    Route::post('/sub-accounts/{subAccount}/transfer', [SubAccountController::class, 'transfer'])->name('sub-accounts.transfer');
    Route::post('/sub-accounts/{subAccount}/withdraw', [SubAccountController::class, 'withdraw'])->name('sub-accounts.withdraw');
    Route::patch('/sub-accounts/{subAccount}/lock', [SubAccountController::class, 'updateLock'])->name('sub-accounts.lock');
    Route::delete('/sub-accounts/{subAccount}', [SubAccountController::class, 'destroy'])->name('sub-accounts.destroy');

    Route::put('/payment-splits', [PaymentSplitController::class, 'update'])->name('payment-splits.update');
    Route::delete('/payment-splits', [PaymentSplitController::class, 'clear'])->name('payment-splits.clear');
});

Route::post('/webhook/deposit', [WebhookController::class, 'handleDeposit'])->name('webhook.deposit');

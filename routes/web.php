<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\Auth\AdminRegisterController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\Afa\AfaController;

use App\Http\Controllers\Admin\Orders\PendingOrderController;
use App\Http\Controllers\Admin\Orders\ProcessingOrderController;
use App\Http\Controllers\Admin\Orders\InProgressOrderController;
use App\Http\Controllers\Admin\Orders\DeliveredOrderController;

use App\Http\Controllers\Admin\AfaOrders\PendingAfaOrderController;
use App\Http\Controllers\Admin\AfaOrders\ProcessingAfaOrderController;
use App\Http\Controllers\Admin\AfaOrders\InProgressAfaOrderController;
use App\Http\Controllers\Admin\AfaOrders\DeliveredAfaOrderController;

use App\Http\Controllers\Admin\Finance\TopUpController;
use App\Http\Controllers\Admin\Finance\VerifyPaymentController;
use App\Http\Controllers\Admin\Finance\WalletBalanceController;
use App\Http\Controllers\Admin\Finance\TransactionController;

use App\Http\Controllers\Admin\Settings\AutoCompleteSettingController;
use App\Http\Controllers\Admin\Settings\BusinessSettingController;
use App\Http\Controllers\Admin\Settings\AdminAuthSettingController;

use App\Http\Controllers\User\Auth\UserAuthController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\PackageController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\AfaOrderController;
use App\Http\Controllers\User\WalletTopupController;
use App\Http\Controllers\User\OrderTrackingController;
use App\Http\Controllers\User\OrderHistoryController;
use App\Http\Controllers\User\AfaHistoryController;
use App\Http\Controllers\User\UserSettingsController;

use App\Http\Controllers\User\BulkPurchaseController;

use App\Http\Controllers\User\Auth\ForgotPasswordController;

use App\Http\Controllers\Admin\ResultChecker\ResultCheckerController;
use App\Http\Controllers\Admin\ResultChecker\ResultCheckerItemController;

use App\Http\Controllers\User\ResultChecker\ResultCheckerPageController;
use App\Http\Controllers\User\ResultChecker\ResultCheckerPaymentController;


/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/dashboard');


/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::middleware('business.maintenance')
    ->name('user.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::middleware('guest:user')->group(function () {

            Route::get('/login', [UserAuthController::class, 'showLogin'])
                ->name('login');

            Route::post('/login', [UserAuthController::class, 'login'])
                ->name('login.submit');

            Route::get('/register', [UserAuthController::class, 'showRegister'])
                ->name('register');

            Route::post('/register', [UserAuthController::class, 'register'])
                ->name('register.submit');

            Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPassword'])
                ->name('password.forgot');

            Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
                ->name('password.otp.send');

            Route::get('/verify-reset-otp', [ForgotPasswordController::class, 'showVerifyOtp'])
                ->name('password.otp.verify');

            Route::post('/verify-reset-otp', [ForgotPasswordController::class, 'verifyOtp'])
                ->name('password.otp.check');

            Route::get('/reset-password', [ForgotPasswordController::class, 'showResetPassword'])
                ->name('password.reset');

            Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
                ->name('password.reset.update');
        });

        Route::get('/orders/track', [OrderTrackingController::class, 'index'])
            ->name('orders.track');

        Route::get('/packages/{categorySlug}', [PackageController::class, 'index'])
            ->name('packages.index');

        Route::post('/packages/purchase', [PackageController::class, 'purchase'])
            ->name('packages.purchase');

        Route::get('/bulk-purchase', [BulkPurchaseController::class, 'index'])
            ->name('bulk.index');

        Route::post('/bulk-purchase/submit', [BulkPurchaseController::class, 'store'])
            ->name('bulk.store');

        Route::post('/payments/initialize', [PaymentController::class, 'initialize'])
            ->name('payments.initialize');

        Route::post('/payments/verify', [PaymentController::class, 'verifyFromPopup'])
            ->name('payments.verify');

        Route::get('/payments/callback', [PaymentController::class, 'callback'])
            ->name('payments.callback');

        Route::post('/payments/wallet', [PaymentController::class, 'payWithWallet'])
            ->name('payments.wallet');

        Route::get('/afa/{slug}', [AfaOrderController::class, 'show'])
            ->name('afa.show');

        Route::post('/afa/{slug}/initialize', [AfaOrderController::class, 'initialize'])
            ->name('afa.payment.initialize');

        Route::post('/afa/payment/verify', [AfaOrderController::class, 'verify'])
            ->name('afa.payment.verify');

        /*
        |--------------------------------------------------------------------------
        | Result Checker Routes - Popup JS + Wallet + Guest/User
        |--------------------------------------------------------------------------
        */

        Route::get('/result-checkers/{categorySlug}', [ResultCheckerPageController::class, 'showCategory'])
            ->name('result-checkers.show');

        Route::post('/result-checkers/payment/initialize', [ResultCheckerPaymentController::class, 'initialize'])
            ->name('result-checkers.payment.initialize');

        Route::post('/result-checkers/payment/verify', [ResultCheckerPaymentController::class, 'verify'])
            ->name('result-checkers.payment.verify');

        Route::post('/result-checkers/payment/wallet', [ResultCheckerPaymentController::class, 'payWithWallet'])
            ->name('result-checkers.payment.wallet');

        Route::get('/result-checkers/payment/success/{orderNumber}', [ResultCheckerPaymentController::class, 'success'])
            ->name('result-checkers.payment.success');

        Route::get('/afa-history', [AfaHistoryController::class, 'index'])
            ->name('afa.history');

        Route::get('/orders', [OrderHistoryController::class, 'index'])
            ->name('orders.index');

        Route::get('/wallet', [WalletTopupController::class, 'index'])
            ->name('wallet.index');

        Route::post('/wallet/initialize', [WalletTopupController::class, 'initialize'])
            ->name('wallet.initialize');

        Route::post('/wallet/verify', [WalletTopupController::class, 'verify'])
            ->name('wallet.verify');

        Route::get('/wallet/callback', [WalletTopupController::class, 'callback'])
            ->name('wallet.callback');

        Route::post('/wallet/resume', [WalletTopupController::class, 'resume'])
            ->name('wallet.resume');

        Route::get('/settings', [UserSettingsController::class, 'index'])
            ->name('settings.index');

        Route::post('/settings/profile', [UserSettingsController::class, 'updateProfile'])
            ->name('settings.profile.update');

        Route::post('/settings/password', [UserSettingsController::class, 'updatePassword'])
            ->name('settings.password.update');

        Route::post('/logout', [UserAuthController::class, 'logout'])
            ->name('logout');
    });
    

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/register', [AdminRegisterController::class, 'create'])
            ->name('register');

        Route::post('/register', [AdminRegisterController::class, 'store'])
            ->middleware('admin.auth.access:register')
            ->name('register.store');

        Route::get('/login', [AdminLoginController::class, 'create'])
            ->name('login');

        Route::post('/login', [AdminLoginController::class, 'store'])
            ->middleware('admin.auth.access:login')
            ->name('login.store');
    });

    Route::post('/logout', [AdminLoginController::class, 'destroy'])
        ->name('logout');

    Route::middleware(['admin', 'business.maintenance'])->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{user_id}', [UserController::class, 'show'])->name('show');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user_id}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user_id}', [UserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category_id}', [CategoryController::class, 'show'])->name('show');
            Route::put('/{category_id}', [CategoryController::class, 'update'])->name('update');
            Route::patch('/{category_id}/status', [CategoryController::class, 'updateStatus'])->name('status');
            Route::delete('/{category_id}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('afa')->name('afa.')->group(function () {
            Route::get('/', [AfaController::class, 'index'])->name('index');
            Route::post('/', [AfaController::class, 'store'])->name('store');
            Route::patch('/{id}/status', [AfaController::class, 'status'])->name('status');
            Route::get('/{id}', [AfaController::class, 'show'])->name('show');
            Route::put('/{id}', [AfaController::class, 'update'])->name('update');
            Route::delete('/{id}', [AfaController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [PlanController::class, 'index'])->name('index');
            Route::post('/', [PlanController::class, 'store'])->name('store');
            Route::get('/{plan_id}', [PlanController::class, 'show'])->name('show');
            Route::put('/{plan_id}', [PlanController::class, 'update'])->name('update');
            Route::patch('/{plan_id}/status', [PlanController::class, 'status'])->name('status');
            Route::delete('/{plan_id}', [PlanController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/pending', [PendingOrderController::class, 'index'])->name('pending');
            Route::get('/processing', [ProcessingOrderController::class, 'index'])->name('processing');

            Route::post('/processing/mark-in-progress', [ProcessingOrderController::class, 'markAsInProgress'])
                ->name('processing.mark-in-progress');

            Route::get('/in-progress', [InProgressOrderController::class, 'index'])
                ->name('in-progress');

            Route::post('/in-progress/check-auto-complete', [InProgressOrderController::class, 'checkAutoComplete'])
                ->name('in-progress.check-auto-complete');

            Route::post('/in-progress/complete', [InProgressOrderController::class, 'complete'])
                ->name('in-progress.complete');

            Route::get('/delivered', [DeliveredOrderController::class, 'index'])
                ->name('delivered');
        });

        Route::prefix('afa-orders')->name('afa-orders.')->group(function () {
            Route::get('/pending', [PendingAfaOrderController::class, 'index'])
                ->name('pending');

            Route::get('/processing', [ProcessingAfaOrderController::class, 'index'])
                ->name('processing');

            Route::post('/processing/mark-in-progress', [ProcessingAfaOrderController::class, 'markAsInProgress'])
                ->name('processing.mark-in-progress');

            Route::get('/in-progress', [InProgressAfaOrderController::class, 'index'])
                ->name('in-progress');

            Route::post('/in-progress/complete', [InProgressAfaOrderController::class, 'complete'])
                ->name('in-progress.complete');

            Route::get('/delivered', [DeliveredAfaOrderController::class, 'index'])
                ->name('delivered');
        });

        Route::prefix('finance')->name('finance.')->group(function () {
            Route::get('/transactions', [TransactionController::class, 'index'])
                ->name('transactions');

            Route::get('/transactions/data', [TransactionController::class, 'data'])
                ->name('transactions.data');

            Route::get('/topups', [TopUpController::class, 'index'])
                ->name('topups.index');

            Route::post('/topups', [TopUpController::class, 'store'])
                ->name('topups.store');

            Route::get('/verify-payments', [VerifyPaymentController::class, 'index'])
                ->name('verify-payments');

            Route::get('/verify-payments/search', [VerifyPaymentController::class, 'search'])
                ->name('verify-payments.search');

            Route::post('/verify-payments/paystack', [VerifyPaymentController::class, 'verifyPaystack'])
                ->name('verify-payments.paystack');

            Route::post('/verify-payments/manual', [VerifyPaymentController::class, 'verifyManual'])
                ->name('verify-payments.manual');

            Route::post('/verify-payments/paystack/bulk', [VerifyPaymentController::class, 'verifyBulkPaystack'])
                ->name('verify-payments.paystack.bulk');

            Route::post('/verify-payments/manual/bulk', [VerifyPaymentController::class, 'verifyBulkManual'])
                ->name('verify-payments.manual.bulk');

            Route::get('/commissions', fn () => view('admin.finance.commissions'))
                ->name('commissions');

            Route::get('/withdrawals', fn () => view('admin.finance.withdrawals'))
                ->name('withdrawals');

            Route::get('/wallet-balances', [WalletBalanceController::class, 'index'])
                ->name('wallet-balances');

            Route::get('/reports', fn () => view('admin.finance.reports'))
                ->name('reports');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', fn () => view('admin.reports.index'))->name('index');
            Route::get('/analytics', fn () => view('admin.reports.analytics'))->name('analytics');
            Route::get('/downloads', fn () => view('admin.reports.downloads'))->name('downloads');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', fn () => view('admin.roles.index'))->name('index');
            Route::get('/permissions', fn () => view('admin.roles.permissions'))->name('permissions');
        });

        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/', fn () => view('admin.admins.index'))->name('index');
            Route::get('/permissions', fn () => view('admin.admins.permissions'))->name('permissions');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/business', [BusinessSettingController::class, 'index'])
                ->name('business');

            Route::post('/business', [BusinessSettingController::class, 'update'])
                ->name('business.update');

            Route::post('/business/toggle', [BusinessSettingController::class, 'toggle'])
                ->name('business.toggle');

            Route::get('/admin-auth', [AdminAuthSettingController::class, 'index'])
                ->name('admin-auth');

            Route::post('/admin-auth', [AdminAuthSettingController::class, 'update'])
                ->name('admin-auth.update');

            Route::get('/sms', fn () => view('admin.settings.sms'))
                ->name('sms');

            Route::get('/autocomplete', [AutoCompleteSettingController::class, 'index'])
                ->name('autocomplete');

            Route::post('/autocomplete', [AutoCompleteSettingController::class, 'update'])
                ->name('autocomplete.update');
        });

Route::prefix('result-checkers')->name('result-checkers.')->group(function () {

    Route::get('/', [ResultCheckerItemController::class, 'index'])->name('index');

    Route::get('/create', [ResultCheckerItemController::class, 'create'])->name('create');
    Route::post('/store', [ResultCheckerItemController::class, 'store'])->name('store');
    Route::put('/{checker_id}', [ResultCheckerItemController::class, 'update'])->name('update');
    Route::delete('/{checker_id}', [ResultCheckerItemController::class, 'destroy'])->name('destroy');
    Route::post('/{checker_id}/import-vouchers', [ResultCheckerItemController::class, 'importVouchers'])->name('vouchers.import');

    Route::get('/voucher-template/download', [ResultCheckerItemController::class, 'downloadVoucherTemplate'])
    ->name('vouchers.template');

    Route::get('/categories', [ResultCheckerController::class, 'categories'])->name('categories');
    Route::post('/categories', [ResultCheckerController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category_id}', [ResultCheckerController::class, 'updateCategory'])->name('categories.update');
    Route::patch('/categories/{category_id}/status', [ResultCheckerController::class, 'categoryStatus'])->name('categories.status');
    Route::delete('/categories/{category_id}', [ResultCheckerController::class, 'destroyCategory'])->name('categories.destroy');

    Route::get('/orders', [ResultCheckerController::class, 'orders'])->name('orders');

    Route::get('/reports', [ResultCheckerController::class, 'reports'])->name('reports');
});
    });
});
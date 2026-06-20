<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\PaystackWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Paystack sends payment notifications here.
| This route must NOT use auth middleware.
|
*/

Route::prefix('paystack')->group(function () {

    Route::post('/webhook', [PaystackWebhookController::class, 'handle'])
        ->name('api.paystack.webhook');

});
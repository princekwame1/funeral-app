<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\TextTangoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/paystack/webhook', PaystackWebhookController::class)->name('webhooks.paystack');
Route::post('/texttango/webhook', TextTangoWebhookController::class)->name('webhooks.texttango');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/donations', [DonationController::class, 'index']);
    Route::post('/donations', [DonationController::class, 'store']);
    Route::get('/donations/{donation}', [DonationController::class, 'show']);
    Route::post('/donations/{donation}/verify', [DonationController::class, 'verify']);

    Route::get('/contacts', [\App\Http\Controllers\Api\ContactController::class, 'index']);
    Route::post('/contacts', [\App\Http\Controllers\Api\ContactController::class, 'store']);
    Route::post('/contacts/{contact}/delete', [\App\Http\Controllers\Api\ContactController::class, 'destroy']);

    Route::get('/contact-groups', [\App\Http\Controllers\Api\ContactController::class, 'groupsIndex']);
    Route::post('/contact-groups', [\App\Http\Controllers\Api\ContactController::class, 'groupsStore']);
    Route::post('/contact-groups/{group}/delete', [\App\Http\Controllers\Api\ContactController::class, 'groupsDestroy']);

    Route::get('/events', [\App\Http\Controllers\Api\EventController::class, 'index']);
    Route::post('/events', [\App\Http\Controllers\Api\EventController::class, 'store']);
    Route::post('/events/{event}/delete', [\App\Http\Controllers\Api\EventController::class, 'destroy']);

    Route::get('/sms/templates', [\App\Http\Controllers\Api\SmsController::class, 'templates']);
    Route::post('/sms/send', [\App\Http\Controllers\Api\SmsController::class, 'send']);
});

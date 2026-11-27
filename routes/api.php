<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    // The same settings the apps were built with (mobile/app-config.json), straight from this site.
    Route::get('app-config', fn () => response()->json(\App\Support\MobileConfig::build())->header('Cache-Control', 'public, max-age=300'));
    Route::post('auth/otp/request', [Api\AuthController::class, 'requestOtp'])->middleware('throttle:10,1');
    Route::post('auth/otp/verify', [Api\AuthController::class, 'verifyOtp'])->middleware('throttle:20,1');
    Route::post('auth/google', [Api\AuthController::class, 'google'])->middleware('throttle:20,1');
    Route::get('banners', [Api\CatalogueController::class, 'banners']);
    Route::post('serviceability', [Api\CatalogueController::class, 'serviceability']);
    Route::get('dishes/{dish:slug}', [Api\CatalogueController::class, 'dish']);
    Route::post('webhooks/razorpay', [Api\CheckoutController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [Api\AuthController::class, 'logout']);
        Route::get('me', [Api\MeController::class, 'show']);
        Route::patch('me', [Api\MeController::class, 'update']);
        Route::delete('me', [Api\MeController::class, 'deleteAccount']);
        Route::get('me/health', [Api\MeController::class, 'health']);
        Route::put('me/health', [Api\MeController::class, 'saveHealth']);
        Route::get('me/notifications', [Api\MeController::class, 'notificationPrefs']);
        Route::put('me/notifications', [Api\MeController::class, 'saveNotificationPrefs']);
        Route::post('me/devices', [Api\MeController::class, 'registerDevice']);
        Route::get('me/coach/status', [Api\CoachController::class, 'status']);
        Route::put('me/coach/consent', [Api\CoachController::class, 'consent']);
        Route::put('me/wellness', [Api\CoachController::class, 'sync'])->middleware('throttle:30,1');
        Route::get('me/coach', [Api\CoachController::class, 'show'])->middleware('throttle:30,1');
        Route::get('me/coach/foods', [Api\CoachController::class, 'foods'])->middleware('throttle:30,1');

        Route::get('addresses', [Api\AddressController::class, 'index']);
        Route::post('addresses', [Api\AddressController::class, 'store']);
        Route::put('addresses/{address}', [Api\AddressController::class, 'update']);
        Route::delete('addresses/{address}', [Api\AddressController::class, 'destroy']);
        Route::post('addresses/detect', [Api\AddressController::class, 'detect']);

        Route::get('menu', [Api\CatalogueController::class, 'menu']);
        Route::post('meal-responses', [Api\CatalogueController::class, 'mealResponse']);
        Route::post('banners/{banner}/tap', [Api\CatalogueController::class, 'bannerTap']);

        Route::post('checkout/quote', [Api\CheckoutController::class, 'quote']);
        Route::post('checkout', [Api\CheckoutController::class, 'place'])->middleware('throttle:10,1');
        Route::post('payments/razorpay/verify', [Api\CheckoutController::class, 'verify']);

        Route::get('orders', [Api\OrderController::class, 'index']);
        Route::get('orders/{order}', [Api\OrderController::class, 'show']);
        Route::post('orders/{order}/cancel', [Api\OrderController::class, 'cancel']);
        Route::get('orders/{order}/invoice', [Api\OrderController::class, 'invoice']);

        Route::get('wallet', [Api\WalletController::class, 'show']);
        Route::patch('wallet', [Api\WalletController::class, 'settings']);
        Route::post('wallet/topup', [Api\WalletController::class, 'topup'])->middleware('throttle:10,1');

        Route::post('assistant', [Api\AssistantController::class, 'ask'])->middleware('throttle:20,1');

        // Kitchen staff
        Route::middleware('perm:kitchen.board')->prefix('kitchen')->group(function () {
            Route::get('{kitchen}/board', [Api\KitchenController::class, 'board']);
            Route::post('orders/{order}/transition', [Api\KitchenController::class, 'transition']);
            Route::patch('menus/{menu}', [Api\KitchenController::class, 'menuStatus'])->middleware('perm:kitchen.pause');
            Route::post('{kitchen}/dispatch', [Api\KitchenController::class, 'dispatch'])->middleware('perm:delivery.assign');
        });

        // Delivery partners
        Route::middleware('role:rider')->prefix('rider')->group(function () {
            Route::post('duty', [Api\RiderController::class, 'duty']);
            Route::post('location', [Api\RiderController::class, 'location'])->middleware('throttle:120,1');
            Route::get('today', [Api\RiderController::class, 'today']);
            Route::post('batches/{batch}/respond', [Api\RiderController::class, 'respond']);
            Route::post('batches/{batch}/pickup', [Api\RiderController::class, 'pickup']);
            Route::post('stops/{stop}/deliver', [Api\RiderController::class, 'deliver'])->middleware('throttle:15,1');
            Route::post('stops/{stop}/problem', [Api\RiderController::class, 'problem']);
        });

        // Counter POS
        Route::middleware('perm:pos.use')->prefix('pos')->group(function () {
            Route::get('{kitchen}/menu', [Api\PosController::class, 'menu']);
            Route::post('{kitchen}/quote', [Api\PosController::class, 'quote']);
            Route::post('{kitchen}/orders', [Api\PosController::class, 'order']);
        });
    });
    Route::post('pos/pin', [Api\PosController::class, 'pin'])->middleware('throttle:10,1');
});

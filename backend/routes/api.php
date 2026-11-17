<?php

use App\Http\Controllers\Admin\V1\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\V1\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\V1\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\V1\CollectionController as AdminCollectionController;
use App\Http\Controllers\Admin\V1\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\V1\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\V1\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\V1\ProductController as AdminProductController;
use App\Http\Controllers\Admin\V1\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\V1\ReportController as AdminReportController;
use App\Http\Controllers\Admin\V1\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\V1\TagController as AdminTagController;
use App\Http\Controllers\Admin\V1\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FulfillmentController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\PopularProductController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/guest', [AuthController::class, 'guest']);
    });

    // —— كتالوج عام ——
    Route::get('home', HomeController::class);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);
    Route::get('products/popular', [PopularProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('collections', [CollectionController::class, 'index']);
    Route::get('collections/{collection}', [CollectionController::class, 'show']);
    Route::post('coupons/apply', [CouponController::class, 'apply']);
    Route::post('payments/webhook', PaymentWebhookController::class);

    Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
        // —— الحساب ——
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateProfile']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/me', [AuthController::class, 'updateProfile']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // —— السلة ——
        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/items', [CartController::class, 'storeItem']);
        Route::patch('cart/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('cart/items/{item}', [CartController::class, 'destroyItem']);
        Route::delete('cart', [CartController::class, 'destroy']);
        Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);

        // —— المواعيد ثم تأكيد الطلب ——
        Route::get('fulfillment/available-dates', [FulfillmentController::class, 'availableDates']);
        Route::get('fulfillment/slots', [FulfillmentController::class, 'slots']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/pay', [OrderController::class, 'pay']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        // —— الإشعارات والأجهزة ——
        Route::post('devices', [DeviceController::class, 'store']);
        Route::delete('devices', [DeviceController::class, 'destroy']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

        Route::middleware('throttle:api')->group(function () {
            Route::get('ai/status', [AiController::class, 'status']);
            Route::post('ai/chat', [AiController::class, 'chat']);
            Route::get('ai/conversations', [AiController::class, 'conversations']);
        });
    });

    Route::prefix('admin')->group(function () {
        Route::middleware('throttle:auth')->group(function () {
            Route::post('auth/login', [AdminAuthController::class, 'login']);
        });

        Route::middleware(['auth:sanctum', 'account.active', 'staff'])->group(function () {
            Route::post('auth/logout', [AdminAuthController::class, 'logout']);
            Route::get('auth/me', [AdminAuthController::class, 'me']);

            Route::apiResource('users', AdminUserController::class);
            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('products', AdminProductController::class);
            Route::apiResource('tags', AdminTagController::class);
            Route::apiResource('banners', AdminBannerController::class);
            Route::apiResource('collections', AdminCollectionController::class);
            Route::apiResource('coupons', AdminCouponController::class);

            Route::get('settings', [AdminSettingController::class, 'index']);
            Route::put('settings', [AdminSettingController::class, 'update']);

            Route::get('customers', [AdminCustomerController::class, 'index']);
            Route::get('customers/{customer}', [AdminCustomerController::class, 'show']);
            Route::post('customers/{customer}/block', [AdminCustomerController::class, 'block']);

            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{order}', [AdminOrderController::class, 'show']);
            Route::post('orders/{order}/accept', [AdminOrderController::class, 'accept']);
            Route::post('orders/{order}/reject', [AdminOrderController::class, 'reject']);
            Route::post('orders/{order}/suggest-slot', [AdminOrderController::class, 'suggestSlot']);
            Route::post('orders/{order}/status', [AdminOrderController::class, 'status']);
            Route::post('orders/{order}/payments', [AdminOrderController::class, 'payments']);
            Route::get('kitchen', [AdminOrderController::class, 'kitchen']);

            Route::get('reports/dashboard', [AdminReportController::class, 'dashboard']);
            Route::get('reports/summary', [AdminReportController::class, 'summary']);
            Route::get('purchases', [AdminPurchaseController::class, 'index']);
            Route::post('purchases', [AdminPurchaseController::class, 'store']);
        });
    });
});

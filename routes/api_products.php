<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductManagementController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdvancedPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Products & Payments API Routes
|--------------------------------------------------------------------------
|
| All routes related to products, cart, and payments
|
*/

// Public product routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/categories/all', [ProductController::class, 'categories']);
    Route::get('/category/{categoryId}', [ProductController::class, 'productsByCategory']);

});

// Protected routes
Route::middleware(['api.key', 'auth:sanctum'])->group(function () {

    // Product Management Routes (Admin)
    Route::prefix('admin/products')->group(function () {


        Route::get('/', [ProductManagementController::class, 'getProducts']);
        Route::post('/', [ProductManagementController::class, 'store']);
        Route::put('/{id}', [ProductManagementController::class, 'update']);
        Route::delete('/{id}', [ProductManagementController::class, 'destroy']);

        // Categories
        Route::get('/categories', [ProductManagementController::class, 'getCategory']);
        Route::post('/categories', [ProductManagementController::class, 'storeCategory']);
        Route::put('/categories/{id}', [ProductManagementController::class, 'updateCategory']);
        Route::delete('/categories/{id}', [ProductManagementController::class, 'destroyCategory']);

        // Variants
        Route::get('/variants', [ProductManagementController::class, 'getVariant']);
        Route::post('/variants', [ProductManagementController::class, 'storeVariant']);
        Route::put('/variants/{id}', [ProductManagementController::class, 'updateVariant']);
        Route::delete('/variants/{id}', [ProductManagementController::class, 'destroyVariant']);

        // Attributes
        Route::post('/attributes', [ProductManagementController::class, 'storeAttribute']);

        // Attribute Values
        Route::get('/attribute-values', [ProductManagementController::class, 'getAttributeValues']);
        Route::post('/attribute-values', [ProductManagementController::class, 'storeAttributeValue']);
        Route::put('/attribute-values/{id}', [ProductManagementController::class, 'updateAttributeValue']);
        Route::delete('/attribute-values/{id}', [ProductManagementController::class, 'destroyAttributeValue']);



    });



    // // Cart Routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/clear', [CartController::class, 'clear']);
    });

    // // Payment Routes
    Route::prefix('payments')->group(function () {
        // Basic payment routes
        Route::get('/methods', [PaymentController::class, 'paymentMethods']);
        Route::post('/process', [PaymentController::class, 'processPayment']);
        Route::get('/orders', [PaymentController::class, 'orders']);
        Route::get('/orders/{id}', [PaymentController::class, 'orderDetails']);
        Route::put('/orders/{id}/status', [PaymentController::class, 'updateOrderStatus']);

        // Advanced payment routes
        Route::post('/initialize', [AdvancedPaymentController::class, 'initializePayment']);
        Route::post('/verify', [AdvancedPaymentController::class, 'verifyPayment']);
        Route::get('/history', [AdvancedPaymentController::class, 'paymentHistory']);

        // Refund routes
        Route::post('/refunds', [AdvancedPaymentController::class, 'processRefund']);
        Route::get('/refunds', [AdvancedPaymentController::class, 'refunds']);
        Route::get('/refunds/{refundId}', [AdvancedPaymentController::class, 'refundDetails']);
    });

    // // Webhook routes for payment gateways
    Route::prefix('webhooks')->group(function () {
        Route::post('/stripe', [AdvancedPaymentController::class, 'handleWebhook'])->name('webhook.stripe');
        Route::post('/paystack', [AdvancedPaymentController::class, 'handleWebhook'])->name('webhook.paystack');
        Route::post('/flutterwave', [AdvancedPaymentController::class, 'handleWebhook'])->name('webhook.flutterwave');
    });


});


<?php

use App\Http\Controllers\Api\OrderTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Tracking API Routes
|--------------------------------------------------------------------------
|
| These routes handle order tracking, shipment management, and analytics
| for the e-commerce platform.
|
*/

Route::prefix('tracking')->middleware(['auth:api'])->group(function () {

    // Order tracking endpoints
    Route::get('/orders/{orderId}', [OrderTrackingController::class, 'trackOrder']);
    Route::put('/orders/{orderId}/status', [OrderTrackingController::class, 'updateStatus']);

    // Shipment management endpoints
    Route::post('/orders/{orderId}/shipments', [OrderTrackingController::class, 'createShipment']);
    Route::put('/shipments/{shipmentId}/status', [OrderTrackingController::class, 'updateShipmentStatus']);

    // Analytics and reporting endpoints
    Route::get('/analytics', [OrderTrackingController::class, 'getAnalytics']);
    Route::get('/real-time-stats', [OrderTrackingController::class, 'getRealTimeStats']);

    // Customer-specific endpoints
    Route::get('/customers/{customerId}/orders', [OrderTrackingController::class, 'getCustomerOrders']);

    // Search functionality
    Route::get('/search', [OrderTrackingController::class, 'searchOrders']);

});

// Public tracking endpoint (no authentication required)
Route::get('/track/{orderNumber}', [OrderTrackingController::class, 'trackByOrderNumber'])
    ->middleware('throttle:60,1');

// Webhook endpoints for external services (e.g., shipping carriers)
Route::post('/webhooks/shipment-updates', [OrderTrackingController::class, 'handleShipmentWebhook'])
    ->middleware('webhook.signature');

// Admin-specific endpoints
Route::prefix('admin/tracking')->middleware(['auth:api', 'role:admin'])->group(function () {

    Route::get('/dashboard', [OrderTrackingController::class, 'getAdminDashboard']);
    Route::get('/reports/sales', [OrderTrackingController::class, 'getSalesReport']);
    Route::get('/reports/shipping', [OrderTrackingController::class, 'getShippingReport']);

});



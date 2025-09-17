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


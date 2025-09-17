<?php


use App\Http\Controllers\Api\DonationCampaignController;
use App\Http\Controllers\Api\DonationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Donation API Routes
|--------------------------------------------------------------------------
| All donation-related API routes

*/

// Public donation routes (no authentication required)
Route::prefix('donations')->group(function () {

    // Donation Categories (Public - for listing and viewing)
    Route::prefix('categories')->group(function () {
        Route::get('/', [DonationController::class, 'donationCategoryList']);
        Route::get('/{category}', [DonationController::class, 'findCategoryById']);
        Route::post('/check-name', [DonationController::class, 'checkNameExists']);
    });

    // Donation Campaigns (Public - for viewing)
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [DonationCampaignController::class, 'index']);
        Route::get('/{campaign}', [DonationCampaignController::class, 'show']);
        Route::get('/active', [DonationCampaignController::class, 'active']);
    });

});

// Protected donation routes (require authentication)
Route::middleware(['api.key', 'auth:sanctum'])->prefix('donations')->group(function () {

    // Donation Categories (Protected - for management)
    Route::prefix('categories')->group(function () {
        Route::post('/', [DonationController::class, 'donationCategoryCreate']);
        Route::put('/{category}', [DonationController::class, 'donationCategoryUpdate']);
        Route::delete('/{category}', [DonationController::class, 'donationCategoryDelete']);
    });

    // Donation Campaigns (Protected - for management)
    Route::prefix('campaigns')->group(function () {
        Route::post('/', [DonationCampaignController::class, 'store']);
        Route::put('/{campaign}', [DonationCampaignController::class, 'update']);
        Route::delete('/{campaign}', [DonationCampaignController::class, 'destroy']);
        Route::get('/{campaign}/donations', [DonationCampaignController::class, 'donations']);
    });

    // Donations (Protected - all operations)
    Route::get('/', [DonationController::class, 'index']);
    Route::post('/', [DonationController::class, 'store']);
    Route::get('/{donation}', [DonationController::class, 'show']);
    Route::put('/{donation}', [DonationController::class, 'update']);
    Route::delete('/{donation}', [DonationController::class, 'destroy']);
    Route::get('/campaign/{campaign}', [DonationController::class, 'byCampaign']);
    Route::get('/statistics', [DonationController::class, 'statistics']);
    Route::get('/user/{user}', [DonationController::class, 'byUser']);

});

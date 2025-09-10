<?php
use App\Http\Controllers\Api\DonationCategoryController;
use App\Http\Controllers\Api\DonationCampaignController;
use App\Http\Controllers\Api\DonationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Donation API Routes
|--------------------------------------------------------------------------
| All donation-related API routes

*/


Route::prefix('donations')->group(function () {

    // Donation Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [DonationCategoryController::class, 'index']);
        Route::post('/', [DonationCategoryController::class, 'store']);
        Route::get('/{category}', [DonationCategoryController::class, 'show']);
        Route::put('/{category}', [DonationCategoryController::class, 'update']);
        Route::delete('/{category}', [DonationCategoryController::class, 'destroy']);
        Route::get('/active', [DonationCategoryController::class, 'active']);
        Route::post('/check-name', [DonationCategoryController::class, 'checkNameExists']);
    });


    // Donation Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [DonationCampaignController::class, 'index']);
        Route::post('/', [DonationCampaignController::class, 'store']);
        Route::get('/{campaign}', [DonationCampaignController::class, 'show']);
        Route::put('/{campaign}', [DonationCampaignController::class, 'update']);
        Route::delete('/{campaign}', [DonationCampaignController::class, 'destroy']);
        Route::get('/{campaign}/donations', [DonationCampaignController::class, 'donations']);
        Route::get('/active', [DonationCampaignController::class, 'active']);
    });

    // Donations
    Route::get('/', [DonationController::class, 'index']);
    Route::post('/', [DonationController::class, 'store']);
    Route::get('/{donation}', [DonationController::class, 'show']);
    Route::put('/{donation}', [DonationController::class, 'update']);
    Route::delete('/{donation}', [DonationController::class, 'destroy']);
    Route::get('/campaign/{campaign}', [DonationController::class, 'byCampaign']);
    Route::get('/statistics', [DonationController::class, 'statistics']);
    Route::get('/user/{user}', [DonationController::class, 'byUser']);

    

});

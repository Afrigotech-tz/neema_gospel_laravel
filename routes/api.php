<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// OTP verification routes
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
});

// Countries public routes (for dropdowns, etc.)
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/list', [CountryController::class, 'list']);
Route::get('/countries/search', [CountryController::class, 'search']);
Route::get('/countries/{id}', [CountryController::class, 'show']);
Route::get('/countries/{id}/users', [CountryController::class, 'users']);

// Simple language endpoint - returns available languages for frontend
Route::get('/languages', function () {
    return response()->json([
        'success' => true,
        'data' => [
            [
                'id' => 1,
                'name' => 'English',
                'native_name' => 'English',
                'code' => 'en',
                'locale' => 'en_US',
                'is_default' => true,
            ],
            [
                'id' => 2,
                'name' => 'Swahili',
                'native_name' => 'Kiswahili',
                'code' => 'sw',
                'locale' => 'sw_TZ',
                'is_default' => false,
            ],
        ],
        'message' => 'Languages retrieved successfully'
    ]);
});


// Public event routes
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/upcoming', [EventController::class, 'upcoming']);
    Route::get('/featured', [EventController::class, 'featured']);
    Route::get('/search', [EventController::class, 'search']);
    Route::get('/{event}', [EventController::class, 'show']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // User routes
    Route::get('/users/search', [UserController::class, 'search']);
    Route::apiResource('users', UserController::class);

    // Country management routes (protected)
    Route::post('/countries', [CountryController::class, 'store']);
    Route::put('/countries/{id}', [CountryController::class, 'update']);
    Route::delete('/countries/{id}', [CountryController::class, 'destroy']);

    // Protected event routes
    Route::prefix('events')->group(function () {
        Route::post('/', [EventController::class, 'store']);
        Route::put('/{event}', [EventController::class, 'update']);
        Route::delete('/{event}', [EventController::class, 'destroy']);
    });

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/picture', [ProfileController::class, 'updateProfilePicture']);
        Route::delete('/picture', [ProfileController::class, 'deleteProfilePicture']);
        Route::post('/location', [ProfileController::class, 'updateLocation']);
    });


});


// Fallback route for API
Route::fallback(function () {

    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found'
    ], 404);


});





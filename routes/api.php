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


// Public user routes
Route::get('test/users/', [UserController::class, 'get_users']);
Route::get('test/users/{id}', [UserController::class, 'get_user']);
Route::put('test/users/{id}', [UserController::class, 'update_user']);
Route::delete('test/users/{id}', [UserController::class, 'delete_user']);
Route::get('test/users/{id}', [UserController::class, 'get_user']);

// OTP verification routes
Route::prefix('auth')->group(function () {
    // Route::post('/send-otp', [AuthController::class, 'sendOtp']);
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

// Public news routes
Route::prefix('news')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NewsController::class, 'index']);
    Route::get('/featured', [App\Http\Controllers\Api\NewsController::class, 'featured']);
    Route::get('/recent', [App\Http\Controllers\Api\NewsController::class, 'recent']);
    Route::get('/{news}', [App\Http\Controllers\Api\NewsController::class, 'show']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // User routes
    Route::get('/users/search', [UserController::class, 'search']);
    Route::apiResource('users', UserController::class);

    // User role management routes
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole']);
    Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole']);
    Route::get('/users/{user}/roles', [UserController::class, 'getUserRoles']);

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

    // Donations routes
    require __DIR__.'/api_donations.php';

    // Music routes
    Route::prefix('music')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\MusicController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\MusicController::class, 'store']);
        Route::get('/{music}', [App\Http\Controllers\Api\MusicController::class, 'show']);
        Route::put('/{music}', [App\Http\Controllers\Api\MusicController::class, 'update']);
        Route::delete('/{music}', [App\Http\Controllers\Api\MusicController::class, 'destroy']);
    });

    // Protected news routes
    Route::prefix('news')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\NewsController::class, 'store']);
        Route::put('/{news}', [App\Http\Controllers\Api\NewsController::class, 'update']);
        Route::delete('/{news}', [App\Http\Controllers\Api\NewsController::class, 'destroy']);
    });

    // Products & Payments Routes
    require __DIR__.'/api_products_payments.php';





});


// Fallback route for API
Route::fallback(function () {

    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found'
    ], 404);


});





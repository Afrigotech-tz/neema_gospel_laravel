<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('neema_gospel');
});

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Please use the API authentication endpoint to login'
    ], 401);
})->name('login');

// Catch-all route to redirect all non-existent pages to neema_gospel
Route::fallback(function () {
    return view('neema_gospel');
});


<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('neema_gospel');
});

Route::any('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Please use the API authentication endpoint to login'
    ], 401);
})->name('login');



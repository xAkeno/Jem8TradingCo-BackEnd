<?php

use App\Http\Controllers\AccountController;
use Illuminate\Http\Request;

Route::post('/login', [AccountController::class, 'login']);
Route::post('/register', [AccountController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function(Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AccountController::class, 'logout']);
});

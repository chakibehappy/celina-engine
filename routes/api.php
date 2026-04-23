<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MobileTestController;

// This is the clean, no-middleware route for testing
Route::post('/test-login', [MobileTestController::class, 'login']);
Route::get('/home-data', [MobileTestController::class, 'getHomeData']);

// Keep this one if you want to test "Real" auth later
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
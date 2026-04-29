<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MobileTestController;

// // This is the clean, no-middleware route for testing
// Route::post('/test-login', [MobileTestController::class, 'login']);
Route::get('/navigation', [MobileTestController::class, 'getNavigation']);
Route::get('/home-data', [MobileTestController::class, 'getHomeData']);

// // Keep this one if you want to test "Real" auth later
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

use App\Http\Controllers\API\MobileAppController;

// Public routes
Route::post('/app/login', [MobileAppController::class, 'login']);
Route::post('/app/google-login', [MobileAppController::class, 'googleLogin']);

// Protected routes (Requires Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/app/logout', [MobileAppController::class, 'logout']);
    Route::get('/app/navigation', [MobileAppController::class, 'getNavigation']);
    Route::get('/app/screen/{route}', [MobileAppController::class, 'getScreenData']);

    Route::get('/app/get-screen-details/{screen_id}', [App\Http\Controllers\Api\ScreenController::class, 'getScreenDetails']);
});
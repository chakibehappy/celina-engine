<?php

use App\Http\Controllers\API\MobileTestController;
use Illuminate\Support\Facades\Route;

// Your new test route
Route::post('/test-login', [MobileTestController::class, 'login']);
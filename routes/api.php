<?php

use App\Http\Controllers\Api\V1\AdController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EmergencyController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\ReviewController;
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

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}/businesses', [CategoryController::class, 'businesses']);

    Route::get('businesses', [BusinessController::class, 'index']);
    Route::get('business/{business}', [BusinessController::class, 'show']);
    Route::get('featured', [BusinessController::class, 'featured']);
    Route::get('nearby', [BusinessController::class, 'nearby']);

    Route::get('business/{business}/reviews', [ReviewController::class, 'index']);
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('ads', [AdController::class, 'index']);
    Route::post('ads/{ad}/click', [AdController::class, 'click']);
    Route::get('emergency', [EmergencyController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        });

        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('business', [BusinessController::class, 'store']);
        Route::put('business/{business}', [BusinessController::class, 'update']);

        Route::post('review', [ReviewController::class, 'store']);

        Route::post('jobs', [JobController::class, 'store']);
        Route::post('job/apply', [JobController::class, 'apply']);
    });
});

<?php

use App\Http\Controllers\Api\V1\AdController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EmergencyController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

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
        Route::post('send-login-otp', [AuthController::class, 'sendLoginOtp']);
        Route::post('verify-login-otp', [AuthController::class, 'verifyLoginOtp']);
        Route::post('verify-otp', [AuthController::class, 'verifyRegistrationOtp']);
        Route::post('resend-otp', [AuthController::class, 'resendRegistrationOtp']);
        Route::post('forgot-password', function (Request $request) {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['message' => 'Reset link sent successfully.']);
            }

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        });
        Route::post('social', [\App\Http\Controllers\Api\V1\SocialAuthController::class, 'token']);
        Route::post('verify-widget-token', [AuthController::class, 'verifyWidgetToken']);
    });

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}/businesses', [CategoryController::class, 'businesses']);

    Route::middleware('throttle:60,1')->get('businesses', [BusinessController::class, 'index']);
    Route::get('businesses/{business}', [BusinessController::class, 'show']);
    Route::get('featured', [BusinessController::class, 'featured']);
    Route::get('nearby', [BusinessController::class, 'nearby']);
    Route::get('businesses/{business}/reviews', [ReviewController::class, 'index']);
    Route::get('businesses/{business}/products', [\App\Http\Controllers\Api\V1\ProductController::class, 'index']);
    Route::get('businesses/{business}/products/{product}', [\App\Http\Controllers\Api\V1\ProductController::class, 'show']);
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('ads', [AdController::class, 'index']);
    Route::post('ads/{ad}/click', [AdController::class, 'click']);
    Route::get('emergency', [EmergencyController::class, 'index']);
    Route::get('businesses/{business}/payments', [PaymentController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        });

        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('businesses', [BusinessController::class, 'store']);
        Route::put('businesses/{business}', [BusinessController::class, 'update']);
        Route::post('businesses/{business}/products', [\App\Http\Controllers\Api\V1\ProductController::class, 'store']);
        Route::put('businesses/{business}/products/{product}', [\App\Http\Controllers\Api\V1\ProductController::class, 'update']);
        Route::delete('businesses/{business}/products/{product}', [\App\Http\Controllers\Api\V1\ProductController::class, 'destroy']);
        Route::post('businesses/{business}/like', [BusinessController::class, 'like']);
        Route::delete('businesses/{business}/like', [BusinessController::class, 'unlike']);

        // Ads management (admins/moderators)
        Route::put('ads/{ad}', [AdController::class, 'update']);
        Route::delete('ads/{ad}', [AdController::class, 'destroy']);

        Route::post('review', [ReviewController::class, 'store']);

        Route::post('jobs', [JobController::class, 'store']);
        Route::post('job/apply', [JobController::class, 'apply']);

        Route::post('businesses/{business}/payment', [PaymentController::class, 'store']);
    });
});

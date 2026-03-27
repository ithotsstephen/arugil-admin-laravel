<?php

use App\Http\Controllers\Api\V1\AdController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EmergencyController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
            $data = $request->validate(['email' => 'required|email']);

            try {
                $email = strtolower($data['email']);
                $user = User::where('email', $email)->first();

                if (! $user) {
                    // Keep response generic to avoid user enumeration.
                    return response()->json(['message' => 'If this email exists, an OTP has been sent.']);
                }

                $otp = (string) random_int(100000, 999999);
                Cache::put('forgot_password_otp:' . $email, $otp, now()->addMinutes(10));

                Mail::raw("Your password reset OTP is: {$otp}. It expires in 10 minutes.", function ($m) use ($email) {
                    $m->to($email)->subject('Password Reset OTP');
                });

                return response()->json(['message' => 'OTP sent successfully.']);
            } catch (\Throwable $e) {
                Log::error('Forgot password API failed', [
                    'email' => $data['email'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Unable to send OTP right now. Please try again.',
                ], 500);
            }
        });
        Route::post('verify-forgot-otp', function (Request $request) {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'otp' => ['required', 'string', 'size:6'],
            ]);

            $email = strtolower($data['email']);
            $cachedOtp = Cache::get('forgot_password_otp:' . $email);

            if (!$cachedOtp || $cachedOtp !== $data['otp']) {
                return response()->json(['message' => 'Invalid or expired OTP.'], 422);
            }

            return response()->json(['message' => 'OTP verified successfully.']);
        });
        Route::post('reset-password', function (Request $request) {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'token' => ['nullable', 'string', 'required_without:otp'],
                'otp' => ['nullable', 'string', 'size:6', 'required_without:token'],
            ]);

            if (!empty($data['otp'])) {
                $email = strtolower($data['email']);
                $cachedOtp = Cache::get('forgot_password_otp:' . $email);

                if (!$cachedOtp || $cachedOtp !== $data['otp']) {
                    return response()->json(['message' => 'Invalid or expired OTP.'], 422);
                }

                $user = User::where('email', $email)->first();
                if (!$user) {
                    return response()->json(['message' => 'Unable to reset password.'], 422);
                }

                $user->forceFill([
                    'password' => Hash::make($data['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                Cache::forget('forgot_password_otp:' . $email);
                event(new PasswordReset($user));

                return response()->json(['message' => 'Password reset successfully.']);
            }

            $status = Password::broker('users')->reset(
                $data,
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json(['message' => 'Password reset successfully.']);
            }

            return response()->json(['message' => __($status)], 422);
        });
        Route::post('social', [\App\Http\Controllers\Api\V1\SocialAuthController::class, 'token']);
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

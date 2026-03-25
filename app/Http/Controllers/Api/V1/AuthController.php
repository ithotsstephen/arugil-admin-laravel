<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Store registration data in cache pending OTP verification
        Cache::put('register_data:' . $data['email'], [
            'full_name' => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
        ], now()->addMinutes(30));

        // Generate and send OTP
        $otp = rand(100000, 999999);
        Cache::put('register_otp:' . $data['email'], (string) $otp, now()->addMinutes(10));

        try {
            Mail::raw("Your registration OTP: {$otp}", function ($m) use ($data) {
                $m->to($data['email'])->subject('Complete your registration');
            });
        } catch (\Throwable $e) {
            Log::error('Registration OTP email send failed', ['email' => $data['email'], 'error' => $e->getMessage()]);
            // OTP is cached; warn the client but don't fail the request
            return response()->json([
                'message' => 'OTP generated but email delivery failed. Please use resend-otp.',
                'email'   => $data['email'],
            ], 202);
        }

        return response()->json([
            'message' => 'OTP sent to your email. Please verify to complete registration.',
            'email'   => $data['email'],
        ], 200);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $data['login'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Step 1 of OTP login: validate credentials, send OTP to email.
     * POST /api/v1/auth/send-login-otp
     * Body: { "login": "email_or_phone", "password": "..." }
     */
    public function sendLoginOtp(Request $request)
    {
        $data = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user  = User::where($field, $data['login'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (empty($user->email)) {
            throw ValidationException::withMessages([
                'login' => ['No email address on file for this account.'],
            ]);
        }

        $otp = rand(100000, 999999);
        Cache::put('login_otp:' . $user->email, (string) $otp, now()->addMinutes(10));

        try {
            Mail::raw("Your login OTP: {$otp}", function ($m) use ($user) {
                $m->to($user->email)->subject('Your login OTP');
            });
        } catch (\Throwable $e) {
            Log::error('Login OTP email send failed', ['email' => $user->email, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'OTP generated but email delivery failed. Please try again.',
            ], 202);
        }

        return response()->json([
            'message' => 'OTP sent to your registered email address.',
            'email'   => $user->email,
        ], 200);
    }

    /**
     * Step 2 of OTP login: verify OTP, return Sanctum token.
     * POST /api/v1/auth/verify-login-otp
     * Body: { "email": "...", "otp": "123456" }
     */
    public function verifyLoginOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'string'],
        ]);

        $cached = Cache::get('login_otp:' . $data['email']);
        if (!$cached || $cached !== $data['otp']) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        Cache::forget('login_otp:' . $data['email']);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 200);
    }

    public function verifyRegistrationOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('register_otp:' . $data['email']);
        if (!$cachedOtp || $cachedOtp !== $data['otp']) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP'],
            ]);
        }

        $reg = \Illuminate\Support\Facades\Cache::get('register_data:' . $data['email']);
        if (!$reg) {
            throw ValidationException::withMessages([
                'email' => ['No pending registration found for this email'],
            ]);
        }

        $user = User::where('email', $reg['email'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $reg['full_name'],
                'email' => $reg['email'],
                'phone' => $reg['phone'] ?? null,
                'password' => $reg['password'],
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        } else {
            if (!$user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        \App\Models\MobileUser::updateOrCreate(
            ['email' => $reg['email']],
            [
                'full_name' => $reg['full_name'],
                'phone' => $reg['phone'] ?? null,
                'password' => $reg['password'],
                'email_verified_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\Cache::forget('register_otp:' . $data['email']);
        \Illuminate\Support\Facades\Cache::forget('register_data:' . $data['email']);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Email verified successfully',
        ], 200);
    }

    public function resendRegistrationOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $reg = \Illuminate\Support\Facades\Cache::get('register_data:' . $data['email']);
        if (!$reg) {
            throw ValidationException::withMessages([
                'email' => ['No pending registration found for this email'],
            ]);
        }

        $otp = rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('register_otp:' . $data['email'], (string)$otp, now()->addMinutes(10));

        try {
            \Illuminate\Support\Facades\Mail::raw("Your registration OTP: {$otp}", function ($m) use ($data) {
                $m->to($data['email'])->subject('Complete your registration');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Resend registration OTP email failed', ['email' => $data['email'], 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'OTP resent to your email'], 200);
    }

    /**
     * Verify MSG91 widget access token on the server side.
     * Expects JSON: { "access_token": "<jwt_from_widget>" }
     */
    public function verifyWidgetToken(Request $request)
    {
        $data = $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $authKey = config('services.msg91.authkey');
        $verifyUrl = config('services.msg91.verify_url');

        if (empty($authKey)) {
            return response()->json(['message' => 'MSG91 auth key not configured'], 500);
        }

        try {
            $resp = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($verifyUrl, [
                'authkey' => $authKey,
                'access-token' => $data['access_token'],
            ]);

            $body = $resp->json();

            // If verification succeeded, attempt to extract phone number and create/find user
            if ($resp->successful()) {
                $phone = null;

                // helper: recursively search for likely phone keys
                $searchKeys = ['mobile', 'phone', 'msisdn', 'number'];
                $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($body));
                foreach ($iterator as $key => $value) {
                    if (in_array(strtolower($key), $searchKeys, true)) {
                        $phone = (string) $value;
                        break;
                    }
                }

                // also check top-level 'data' array common structure
                if (!$phone && isset($body['data']) && is_array($body['data'])) {
                    foreach ($searchKeys as $k) {
                        if (!empty($body['data'][$k])) {
                            $phone = (string) $body['data'][$k];
                            break;
                        }
                    }
                }

                if ($phone) {
                    // normalize phone: keep digits and plus
                    $normalized = preg_replace('/[^\d+]/', '', $phone);

                    $user = \App\Models\User::where('phone', $normalized)->first();
                    if (! $user) {
                        $user = \App\Models\User::create([
                            'name' => '',
                            'phone' => $normalized,
                            'role' => 'user',
                            'status' => 'active',
                        ]);
                    } else {
                        // ensure status/role set
                        $user->update(['status' => $user->status ?? 'active']);
                    }

                    $token = $user->createToken('api')->plainTextToken;

                    return response()->json([
                        'verified' => true,
                        'user' => $user,
                        'token' => $token,
                        'provider_response' => $body,
                    ], 200);
                }
            }

            // If we couldn't extract/store a phone, return provider response as-is
            return response()->json($body, $resp->status());
        } catch (\Exception $e) {
            Log::error('MSG91 token verify failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Verification failed'], 502);
        }
    }
}

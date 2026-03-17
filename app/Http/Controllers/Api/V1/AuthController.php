<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
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
                            break 2;
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

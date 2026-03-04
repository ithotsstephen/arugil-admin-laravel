<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // Web redirect to provider (e.g., /auth/redirect/google)
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    // Web callback from provider
    public function callback(Request $request, string $provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->findOrCreateUser($socialUser, $provider);

        $token = $user->createToken('api')->plainTextToken;

        // For web flow, redirect to frontend with token (adjust URL as needed)
        $redirectUrl = config('app.client_url', env('APP_URL')) . '/?token=' . $token;

        return redirect($redirectUrl);
    }

    // API flow: exchange provider access token for app token
    public function token(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', 'string'],
            'access_token' => ['required', 'string'],
        ]);

        $provider = $data['provider'];

        $socialUser = Socialite::driver($provider)->stateless()->userFromToken($data['access_token']);

        $user = $this->findOrCreateUser($socialUser, $provider);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    protected function findOrCreateUser($socialUser, string $provider)
    {
        $email = $socialUser->getEmail();

        if ($email) {
            $user = User::where('email', $email)->first();
        } else {
            $user = User::where('provider_id', $socialUser->getId())
                ->where('provider', $provider)
                ->first();
        }

        if ($user) {
            // update provider info if missing
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);

            return $user;
        }

        // Create a new user
        return User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
            'email' => $email,
            'password' => Hash::make(uniqid('social_', true)),
            'role' => 'user',
            'status' => 'active',
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
        ]);
    }
}

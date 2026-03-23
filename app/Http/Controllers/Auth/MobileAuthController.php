<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.mobile_register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $data['password'] = Hash::make($data['password']);
        // ensure email (if provided) is not already used
        if (!empty($data['email'])) {
            $exists = User::where('email', $data['email'])->exists();
            if ($exists) {
                return back()->withErrors(['email' => 'Email already in use']);
            }
        }

        // cache registration data until OTP verification
        if (!empty($data['email'])) {
            Cache::put('register_data:' . $data['email'], [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ], now()->addMinutes(30));

            // generate and send registration OTP (10 minutes)
            $otp = rand(100000, 999999);
            Cache::put('register_otp:' . $data['email'], (string)$otp, now()->addMinutes(10));

            try {
                Mail::raw("Your registration OTP: {$otp}", function ($m) use ($data) {
                    $m->to($data['email'])->subject('Complete your registration');
                });
            } catch (\Throwable $e) {
                // ignore send failures; user can request resend
            }

            return redirect()->route('mobile.register.verify')->with('email', $data['email'])->with('status', 'OTP sent to your email. Please verify to complete registration.');
        }

        // If no email provided, create user immediately (phone-only registration)
        $u = User::create([
            'name' => $data['full_name'],
            'email' => null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'user',
            'status' => 'active',
        ]);

        MobileUser::create([
            'full_name' => $data['full_name'],
            'email' => null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);

        auth()->loginUsingId($u->id);

        return redirect('/')->with('status', 'Registered and logged in');
    }

    public function showRegisterVerify(Request $request)
    {
        $email = session('email') ?? $request->query('email');
        return view('auth.mobile_register_verify', compact('email'));
    }

    public function verifyRegistrationOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);
        $cachedOtp = Cache::get('register_otp:' . $data['email']);
        if (!$cachedOtp || $cachedOtp !== $data['otp']) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP']);
        }

        $reg = Cache::get('register_data:' . $data['email']);
        if (!$reg) {
            return back()->withErrors(['email' => 'No pending registration found for this email']);
        }

        // create the user now
        $user = User::create([
            'name' => $reg['full_name'],
            'email' => $reg['email'],
            'phone' => $reg['phone'] ?? null,
            'password' => $reg['password'],
            'role' => 'user',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // optional mobile_users record
        MobileUser::create([
            'full_name' => $reg['full_name'],
            'email' => $reg['email'],
            'phone' => $reg['phone'] ?? null,
            'password' => $reg['password'],
        ]);

        Cache::forget('register_otp:' . $data['email']);
        Cache::forget('register_data:' . $data['email']);

        auth()->loginUsingId($user->id);

        return redirect('/')->with('status', 'Registration complete — welcome!');
    }

    public function resendRegistrationOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $reg = Cache::get('register_data:' . $data['email']);
        if (!$reg) {
            return back()->withErrors(['email' => 'No pending registration found for this email']);
        }

        $otp = rand(100000, 999999);
        Cache::put('register_otp:' . $data['email'], (string)$otp, now()->addMinutes(10));

        try {
            Mail::raw("Your registration OTP: {$otp}", function ($m) use ($data) {
                $m->to($data['email'])->subject('Complete your registration');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        return redirect()->back()->with('status', 'OTP resent to your email');
    }

    public function showLogin()
    {
        return view('auth.mobile_login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required_without:phone|nullable|email',
            'phone' => 'required_without:email|nullable|string',
            'password' => 'required|string'
        ]);

        $user = null;
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        } elseif (!empty($data['phone'])) {
            $user = User::where('phone', $data['phone'])->first();
        }

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['credentials' => 'Invalid credentials']);
        }

        auth()->loginUsingId($user->id);
        return redirect('/')->with('status', 'Logged in');
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('status', 'Logged out');
    }

    public function showForgot()
    {
        return view('auth.mobile_forgot');
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No user with that email']);
        }

        $otp = rand(100000, 999999);

        // store OTP in cache for 10 minutes
        Cache::put('mobile_otp:' . $user->email, (string)$otp, now()->addMinutes(10));

        try {
            Mail::raw("Your password reset OTP: {$otp}", function ($m) use ($user) {
                $m->to($user->email)->subject('Password reset OTP');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        return redirect()->back()->with('status', 'OTP sent to your email');
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) return back()->withErrors(['email' => 'No user with that email']);

        $cached = Cache::get('mobile_otp:' . $user->email);
        if (!$cached || $cached !== $data['otp']) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP']);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        Cache::forget('mobile_otp:' . $user->email);

        return redirect('/login')->with('status', 'Password reset — please login');
    }

    // Admin list view
    public function adminIndex()
    {
        $users = MobileUser::orderByDesc('id')->paginate(50);
        return view('admin.mobile_users.index', compact('users'));
    }
}

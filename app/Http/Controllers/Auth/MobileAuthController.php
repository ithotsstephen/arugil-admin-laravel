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
            'email' => 'nullable|email|unique:mobile_users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $data['password'] = Hash::make($data['password']);

        // create / sync into main users table so admin mobile users module shows it
        $u = User::create([
            'name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'user',
            'status' => 'active',
        ]);

        // optional: also store into mobile_users table for backward compatibility
        MobileUser::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);

        // log the user in
        auth()->loginUsingId($u->id);

        return redirect('/')->with('status', 'Registered and logged in');
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

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    public function __construct(
        private UserAccountDeletionService $userAccountDeletionService,
    ) {
    }

    public function show(): View
    {
        return view('account.delete');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'current_password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $data['login'])->first();

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $isCurrentUser = Auth::check() && Auth::id() === $user->id;

        $this->userAccountDeletionService->delete($user);

        if ($isCurrentUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('account.delete.show')
            ->with('status', 'Your account has been deleted successfully.');
    }
}
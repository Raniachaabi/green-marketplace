<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Storefront sign-in for buyers and sellers.
 *
 * Deliberately separate from Filament's /admin/login: that page rejects any
 * user whose canAccessPanel() returns false, which is every non-admin
 * account in this app. Routing the whole storefront's guest redirect through
 * it — as this app briefly did — locked every seller out of their own
 * listings.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Registration leaves email optional (phone is the primary
            // identifier — FR-001), so login must accept either: this field
            // takes whichever one the visitor has, and we detect the shape.
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [$field => $data['email'], 'password' => $data['password']];

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (Auth::user()->isSuspended()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => __('account.account_suspended'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /** Keyed by email + IP, so one noisy IP cannot lock out a shared address alone. */
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

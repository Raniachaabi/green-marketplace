<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Self-service sign-up.
 *
 * Every account starts as a buyer (FR-002 — one account, several roles).
 * Becoming a seller happens later, through the existing onboarding flow at
 * /vendeur/inscription, not at registration time: asking a buyer who just
 * wants to browse for business documents up front is how you lose them
 * before they ever see the catalogue.
 */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:190'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'preferred_locale' => app()->getLocale(),
            'slug' => $this->uniqueSlug($data['full_name']),
        ]);

        $user->grantRole('buyer');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', __('auth.registered'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'membre';

        do {
            $slug = $base.'-'.Str::lower(Str::random(5));
        } while (User::where('slug', $slug)->exists());

        return $slug;
    }
}

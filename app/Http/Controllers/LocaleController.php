<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('marketplace.locales')), 404);

        session(['locale' => $locale]);

        if ($user = $request->user()) {
            $user->forceFill(['preferred_locale' => $locale])->save();
        }

        return back();
    }
}

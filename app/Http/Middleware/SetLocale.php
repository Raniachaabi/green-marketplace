<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * NFR-01 / NFR-02 — resolve the active locale and its text direction.
 *
 * Order of precedence: explicit session choice, then the signed-in user's
 * stored preference, then the browser's Accept-Language, then the app
 * default (Arabic). The resolved direction is shared with every view so the
 * layout can set dir="rtl" without each template thinking about it.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('marketplace.locales'));

        $locale = session('locale')
            ?? $request->user()?->preferred_locale
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        view()->share('locale', $locale);
        view()->share('dir', config("marketplace.locales.$locale.dir", 'ltr'));

        return $next($request);
    }
}

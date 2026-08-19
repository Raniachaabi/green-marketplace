<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\View\View;

/**
 * FR-024 — badge disclosure.
 *
 * Every badge on the site links here, and this page says exactly what was
 * checked, by whom, and how long it is valid. Precision protects the
 * platform; a vague badge does the opposite.
 */
class BadgeController extends Controller
{
    public function show(string $code): View
    {
        $badge = Badge::with('credentialTypes')->findOrFail($code);

        return view('catalog.badge', compact('badge'));
    }
}

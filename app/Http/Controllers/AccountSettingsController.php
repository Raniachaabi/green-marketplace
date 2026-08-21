<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Phase 2 §1 — notification preferences. */
class AccountSettingsController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.settings', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->user()->update([
            'notify_social' => $request->boolean('notify_social'),
            'notify_announcements' => $request->boolean('notify_announcements'),
        ]);

        return back()->with('status', __('account.settings_saved'));
    }
}

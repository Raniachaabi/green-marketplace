<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Phase 2 §8 — "Meet the Producer". Always the current seller's own story; no ownership param needed. */
class SellerStoryController extends Controller
{
    public function show(Request $request): View
    {
        return view('seller.story', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'story' => ['nullable', 'string', 'max:4000'],
            'production_method' => ['nullable', 'string', 'max:2000'],
            'mission' => ['nullable', 'string', 'max:1000'],
            'founding_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        if ($request->hasFile('cover')) {
            if ($user->cover_path) {
                Storage::disk('public')->delete($user->cover_path);
            }

            $data['cover_path'] = $request->file('cover')->store('sellers/'.$user->id, 'public');
        }

        unset($data['cover']);

        $user->update($data);

        return back()->with('status', __('seller.story_saved'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Phase 2 §3 — following a seller. A seller storefront is always a User (§storefront route). */
class FollowController extends Controller
{
    public function index(Request $request): View
    {
        return view('following.index', [
            'sellers' => $request->user()->following()
                ->with('seller')
                ->latest()
                ->paginate(24)
                ->through(fn (Follow $f) => $f->seller),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 422);
        abort_unless($user->listings()->exists(), 404);

        $request->user()->following()->firstOrCreate(['seller_user_id' => $user->id]);

        return back()->with('status', __('follow.followed'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->user()->following()->where('seller_user_id', $user->id)->delete();

        return back()->with('status', __('follow.unfollowed'));
    }
}

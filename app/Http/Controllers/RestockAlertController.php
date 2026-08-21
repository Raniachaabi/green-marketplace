<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Phase 2 §5 — "notify me when available". */
class RestockAlertController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->isOutOfStock(), 422);

        $listing->restockAlerts()->pending()->firstOrCreate(['user_id' => $request->user()->id]);

        return back()->with('status', __('restock.subscribed'));
    }
}

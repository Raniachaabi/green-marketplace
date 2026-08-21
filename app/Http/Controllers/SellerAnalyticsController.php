<?php

namespace App\Http\Controllers;

use App\Services\SellerAnalyticsService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerAnalyticsController extends Controller
{
    public function __construct(private readonly SellerAnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $range = DateRange::fromKey(
            $request->string('range', '30d')->toString(),
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null,
        );

        return view('seller.analytics', [
            'range' => $range,
            'report' => $this->analytics->report($request->user(), $range),
        ]);
    }
}

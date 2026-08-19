<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GreenAttribute;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function home(): View
    {
        $roots = Category::active()->roots()->orderBy('display_order')->get();

        // FR-054 — "in season now". The single most useful home block in an
        // agricultural marketplace, because half the catalogue is only
        // meaningful for a few weeks a year.
        $inSeason = Listing::active()
            ->inSeason()
            ->where('availability_model', 'seasonal')
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->latest('published_at')
            ->take(8)
            ->get();

        $newest = Listing::active()
            ->inSeason()
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->latest('published_at')
            ->take(12)
            ->get();

        return view('catalog.home', compact('roots', 'inSeason', 'newest'));
    }

    /** FR-050 to FR-053 — browse, search, filter, sort. */
    public function index(Request $request): View
    {
        $query = Listing::active()->inSeason()->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes']);

        if ($slug = $request->string('category')->toString()) {
            $category = Category::where('slug', $slug)->first();

            if ($category) {
                // Include descendants, so browsing "Intrants" shows seed too.
                $ids = Category::where('path', 'like', $category->path.'%')->pluck('id');
                $query->whereIn('category_id', $ids);
            }
        }

        if ($term = $request->string('q')->toString()) {
            $query->where(function ($q) use ($term) {
                // Cross-locale search (NFR-04): the JSON title holds every
                // locale, so one LIKE finds an Arabic query in a French title.
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('lot_number', 'like', "%{$term}%");
            });
        }

        $query->inGovernorate($request->string('governorate')->toString() ?: null);

        if ($attributes = array_filter((array) $request->input('green', []))) {
            $query->withGreenAttribute($attributes);
        }

        if ($max = $request->integer('max_price')) {
            $query->where('price', '<=', $max * 1000);
        }

        $query = match ($request->string('sort')->toString()) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'oldest' => $query->oldest('published_at'),
            default => $query->latest('published_at'),
        };

        return view('catalog.index', [
            'listings' => $query->paginate(24)->withQueryString(),
            'categories' => Category::active()->roots()->orderBy('display_order')->get(),
            'greenAttributes' => GreenAttribute::orderBy('display_order')->get(),
            'governorates' => config('marketplace.governorates'),
            'filters' => $request->all(),
        ]);
    }

    public function show(string $slug): View
    {
        $listing = Listing::active()
            ->where('slug', $slug)
            ->with(['media', 'category', 'greenAttributes', 'species', 'sellerUser', 'sellerOrg'])
            ->firstOrFail();

        $listing->increment('view_count');

        return view('catalog.show', [
            'listing' => $listing,
            'fields' => $listing->category->effectiveFields(),
            'rule' => $listing->category->effectiveRule(),
            'priceCap' => $listing->category->activePriceCap(),
            'badges' => $listing->seller()?->activeBadges() ?? collect(),
        ]);
    }
}

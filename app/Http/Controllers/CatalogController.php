<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GreenAttribute;
use App\Models\Listing;
use App\Services\GreenScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly GreenScoreCalculator $greenScore) {}

    public function home(): View
    {
        $roots = Category::active()->roots()->orderBy('display_order')->get();

        // One listing count per root, including its descendants — matches
        // the same "path like" widening the catalogue filter uses.
        $rootCounts = $roots->mapWithKeys(fn (Category $root) => [
            $root->id => Listing::active()->inSeason()
                ->whereIn('category_id', Category::where('path', 'like', $root->path.'%')->pluck('id'))
                ->count(),
        ]);

        // FR-054 — "in season now". The single most useful home block in an
        // agricultural marketplace, because half the catalogue is only
        // meaningful for a few weeks a year.
        $inSeason = Listing::active()
            ->inSeason()
            ->where('availability_model', 'seasonal')
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->latest('published_at')
            ->take(8)
            ->get();

        $newest = Listing::active()
            ->inSeason()
            ->whereHas('category', fn ($q) => $q->where('listing_type', 'product'))
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->latest('published_at')
            ->take(12)
            ->get();

        // Services, experiences and rentals get their own strip — a farm
        // workshop shouldn't be buried in a grid of seed packets.
        $servicesAndExperiences = Listing::active()
            ->inSeason()
            ->whereHas('category', fn ($q) => $q->whereIn('listing_type', ['service', 'experience', 'rental']))
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->latest('published_at')
            ->take(6)
            ->get();

        // A handful of sellers with something live right now, for the trust
        // section — no seller storefront page exists yet, so these are
        // informational cards rather than links.
        $featuredSellers = Listing::active()
            ->inSeason()
            ->with(['sellerUser', 'sellerOrg'])
            ->latest('published_at')
            ->get()
            ->map(fn (Listing $l) => $l->seller())
            ->filter()
            ->unique(fn ($seller) => $seller::class.':'.$seller->id)
            ->take(6)
            ->values();

        return view('catalog.home', compact(
            'roots', 'rootCounts', 'inSeason', 'newest', 'servicesAndExperiences', 'featuredSellers',
        ));
    }

    /** FR-050 to FR-053 — browse, search, filter, sort. */
    public function index(Request $request): View
    {
        $query = Listing::active()->inSeason()
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->withCount('reviews')->withAvg('reviews', 'rating');

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

    public function show(Request $request, string $slug): View
    {
        $listing = Listing::active()
            ->where('slug', $slug)
            ->with([
                'media', 'category', 'greenAttributes', 'species', 'sellerUser', 'sellerOrg',
                'reviews' => fn ($q) => $q->with('author')->latest(),
            ])
            ->firstOrFail();

        $listing->increment('view_count');

        // Same category, not this listing, still buyable now — a cheap way
        // to keep a buyer on the site instead of bouncing after one item.
        $similar = Listing::active()->inSeason()
            ->where('category_id', $listing->category_id)
            ->where('id', '!=', $listing->id)
            ->with(['media', 'category', 'sellerUser', 'sellerOrg'])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('catalog.show', [
            'listing' => $listing,
            'fields' => $listing->category->effectiveFields(),
            'rule' => $listing->category->effectiveRule(),
            'priceCap' => $listing->category->activePriceCap(),
            'badges' => $listing->seller()?->activeBadges() ?? collect(),
            'similar' => $similar,
            'greenScore' => $this->greenScore->scoreFor($listing),
            'alreadySubscribedToRestock' => $request->user()
                ? $listing->restockAlerts()->pending()->where('user_id', $request->user()->id)->exists()
                : false,
            'ratingDistribution' => $listing->ratingDistribution(),
            'isListingOwner' => $request->user()
                ? $request->user()->id === $listing->seller_user_id
                    || ($listing->seller_org_id && $request->user()->organizations()->where('organizations.id', $listing->seller_org_id)->exists())
                : false,
        ]);
    }
}

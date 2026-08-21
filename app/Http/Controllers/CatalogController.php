<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GreenAttribute;
use App\Models\Listing;
use App\Models\RecentlyViewedListing;
use App\Models\SearchQuery;
use App\Models\User;
use App\Services\GreenScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly GreenScoreCalculator $greenScore) {}

    public function home(Request $request): View
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

        return view('catalog.home', array_merge(compact(
            'roots', 'rootCounts', 'inSeason', 'newest', 'servicesAndExperiences', 'featuredSellers',
        ), $this->personalization($request)));
    }

    /**
     * Phase 2 §15 — deterministic, never machine-learned: recently viewed
     * listings, "because you viewed X" from that listing's own category,
     * and new listings from sellers the buyer actually follows. Every
     * recommendation is a real, currently-active listing.
     */
    private function personalization(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return ['recentlyViewed' => collect(), 'becauseYouViewed' => null, 'recommendedFor' => collect(), 'fromFollowedSellers' => collect()];
        }

        $recentlyViewed = Listing::active()
            ->whereIn('id', RecentlyViewedListing::where('user_id', $user->id)
                ->orderByDesc('viewed_at')->limit(8)->pluck('listing_id'))
            ->with(['media', 'category', 'sellerUser', 'sellerOrg'])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->get();

        $lastViewedId = RecentlyViewedListing::where('user_id', $user->id)->orderByDesc('viewed_at')->value('listing_id');
        $lastViewed = $lastViewedId ? Listing::find($lastViewedId) : null;

        $recommendedFor = $lastViewed
            ? Listing::active()->inSeason()
                ->where('category_id', $lastViewed->category_id)
                ->where('id', '!=', $lastViewed->id)
                ->with(['media', 'category', 'sellerUser', 'sellerOrg'])
                ->withCount('reviews')->withAvg('reviews', 'rating')
                ->latest('published_at')
                ->take(6)
                ->get()
            : collect();

        $followedSellerIds = $user->followedSellerIds();
        $fromFollowedSellers = $followedSellerIds->isNotEmpty()
            ? Listing::active()->inSeason()
                ->whereIn('seller_user_id', $followedSellerIds)
                ->with(['media', 'category', 'sellerUser', 'sellerOrg'])
                ->withCount('reviews')->withAvg('reviews', 'rating')
                ->latest('published_at')
                ->take(6)
                ->get()
            : collect();

        return [
            'recentlyViewed' => $recentlyViewed,
            'becauseYouViewed' => $lastViewed,
            'recommendedFor' => $recommendedFor,
            'fromFollowedSellers' => $fromFollowedSellers,
        ];
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

        $term = $request->string('q')->toString();

        if ($term) {
            // Phase 2 §6 — cross-locale (NFR-04) search that now also
            // matches the seller's name and the category's own name, not
            // just the listing's own title/description/lot number.
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('lot_number', 'like', "%{$term}%")
                    ->orWhereHas('sellerUser', fn ($s) => $s->where('full_name', 'like', "%{$term}%"))
                    ->orWhereHas('sellerOrg', fn ($s) => $s->where('legal_name', 'like', "%{$term}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });

            SearchQuery::create(['user_id' => $request->user()?->id, 'term' => $term]);
        }

        $query->inGovernorate($request->string('governorate')->toString() ?: null);

        if ($attributes = array_filter((array) $request->input('green', []))) {
            $query->withGreenAttribute($attributes);
        }

        if ($max = $request->integer('max_price')) {
            $query->where('price', '<=', $max * 1000);
        }

        // §7 — rating, verification, and origin are real, derived signals:
        // an aggregate over actual reviews, an actually-approved credential,
        // and a governorate the seller really has on file. None of these
        // are checkboxes that just set a flag.
        if ($minRating = $request->input('min_rating')) {
            // The threshold is inlined (not bound as a `?` parameter): PDO's
            // sqlite driver binds a PHP float as a string parameter, and
            // SQLite compares a numeric column against a text literal by
            // storage class rather than value — a bound '4.5' would never
            // match a real numeric average, silently returning zero rows.
            // Safe to inline because it is always our own (float) cast,
            // never the raw request string.
            $threshold = (float) $minRating;
            $query->whereRaw(
                "(select avg(rating) from reviews where reviews.target_type = ? and reviews.target_id = listings.id) >= {$threshold}",
                ['listing']
            );
        }

        if ($request->boolean('verified')) {
            $query->where(function ($q) {
                $q->whereHas('sellerUser.credentials', fn ($c) => $c->approved()->unexpired()
                    ->whereHas('credentialType.badges', fn ($b) => $b->where('badges.code', 'verified_seller')))
                    ->orWhereHas('sellerOrg.credentials', fn ($c) => $c->approved()->unexpired()
                        ->whereHas('credentialType.badges', fn ($b) => $b->where('badges.code', 'verified_seller')));
            });
        }

        if ($request->boolean('origin_tn')) {
            $query->whereNotNull('governorate');
        }

        $query = match ($request->string('sort')->toString()) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'oldest' => $query->oldest('published_at'),
            default => $query->latest('published_at'),
        };

        $listings = $query->paginate(24)->withQueryString();

        return view('catalog.index', [
            'listings' => $listings,
            'categories' => Category::active()->roots()->orderBy('display_order')->get(),
            'greenAttributes' => GreenAttribute::orderBy('display_order')->get(),
            'governorates' => config('marketplace.governorates'),
            'filters' => $request->all(),
            'recentSearches' => $this->recentSearches($request),
            'popularSearches' => $this->popularSearches(),
            'popularSellers' => $this->popularSellers(),
            // §6 — an empty-state should still leave the buyer somewhere to
            // go, never a dead end. Same eager loads as the main listing
            // query — <x-listing-card> needs every one of them.
            'recommendations' => $listings->isEmpty()
                ? Listing::active()->inSeason()
                    ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
                    ->withCount('reviews')->withAvg('reviews', 'rating')
                    ->latest('published_at')
                    ->take(6)
                    ->get()
                : collect(),
        ]);
    }

    private function recentSearches(Request $request): Collection
    {
        if (! $request->user()) {
            return collect();
        }

        return SearchQuery::where('user_id', $request->user()->id)
            ->latest('created_at')
            ->limit(20)
            ->pluck('term')
            ->unique()
            ->take(5)
            ->values();
    }

    private function popularSearches(): Collection
    {
        return SearchQuery::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('term, count(*) as aggregate')
            ->groupBy('term')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->pluck('term');
    }

    /** Sellers with the most live listings — the "seller suggestions" for search. */
    private function popularSellers(): Collection
    {
        return User::query()
            ->whereHas('listings', fn ($q) => $q->active())
            ->withCount(['listings' => fn ($q) => $q->active()])
            ->orderByDesc('listings_count')
            ->limit(5)
            ->get();
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

        // §15 — real browsing history for "recently viewed" / "because you
        // viewed X" on the buyer's home page. Guests aren't tracked; there
        // is nowhere personalized to show it to them anyway.
        if ($request->user()) {
            RecentlyViewedListing::updateOrCreate(
                ['user_id' => $request->user()->id, 'listing_id' => $listing->id],
                ['viewed_at' => now()],
            );
        }

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

<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\GreenAttribute;
use App\Models\Listing;
use App\Models\ListingMedia;
use App\Services\Publishing\PublishingGate;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Seller-facing listing management.
 *
 * The listing form is built from the category, not hard-coded: whatever
 * category_fields say, the form renders and validates. That is what lets one
 * form serve certified wheat seed, rose water and a school farm visit.
 */
class SellerListingController extends Controller
{
    public function __construct(private readonly PublishingGate $gate) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $base = $request->user()->listings();

        $counts = (clone $base)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->mapWithKeys(fn ($count, $key) => [$key instanceof ListingStatus ? $key->value : $key => $count]);

        $listings = $request->user()->listings()
            ->with(['category', 'media'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('seller.listings.index', [
            'listings' => $listings,
            'counts' => $counts,
            'total' => $counts->sum(),
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $categoryId = $request->string('category')->toString();
        $category = $categoryId ? Category::find($categoryId) : null;

        return view('seller.listings.create', [
            'categories' => $this->sellableCategories($request),
            'category' => $category,
            'fields' => $category?->effectiveFields() ?? collect(),
            'rule' => $category?->effectiveRule(),
            'priceCap' => $category?->activePriceCap(),
            'greenAttributes' => GreenAttribute::orderBy('display_order')->get(),
            'listing' => null,
            'selectedGreen' => collect(),
        ]);
    }

    public function edit(Request $request, Listing $listing): View
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);

        $listing->load(['category', 'media', 'greenAttributes']);

        $category = $listing->category;

        // Show the seller exactly why it is not live yet, using the real gate
        // rather than a guess.
        $result = $this->gate->check($listing);

        return view('seller.listings.edit', [
            'listing' => $listing,
            'category' => $category,
            'fields' => $category?->effectiveFields() ?? collect(),
            'rule' => $category?->effectiveRule(),
            'priceCap' => $category?->activePriceCap(),
            'greenAttributes' => GreenAttribute::orderBy('display_order')->get(),
            'selectedGreen' => $listing->greenAttributes->pluck('code'),
            'violations' => $result->violations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::findOrFail($request->input('category_id'));

        $data = $this->validateListing($request, $category);

        $listing = new Listing(array_merge($this->attributesFromValidated($data), [
            'seller_user_id' => $request->user()->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Draft,
        ]));

        $listing->save();

        if (! empty($data['green'])) {
            $listing->greenAttributes()->sync($data['green']);
        }

        return redirect()
            ->route('seller.listings.edit', $listing)
            ->with('status', __('seller.listing_saved'));
    }

    public function update(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);

        $category = $listing->category;
        abort_unless($category, 404);

        $data = $this->validateListing($request, $category);
        $wasActive = $listing->status === ListingStatus::Active;

        $listing->fill($this->attributesFromValidated($data));
        $listing->save();

        $listing->greenAttributes()->sync($data['green'] ?? []);

        // FR — editing a live listing sends it back for review
        // (ListingObserver). Tell the seller that happened rather than
        // letting them discover it later on an unpublished listing.
        $message = $wasActive && $listing->status === ListingStatus::Pending
            ? __('seller.listing_resubmitted')
            : __('seller.listing_saved');

        return redirect()
            ->route('seller.listings.edit', $listing)
            ->with('status', $message);
    }

    /**
     * Attempt to publish. The gate is the single authority — this method
     * exists only to translate its verdict into a redirect.
     */
    public function publish(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);

        $result = $this->gate->publish($listing);

        if ($result->fails()) {
            return back()->withErrors(['publish' => $result->messages()]);
        }

        return back()->with('status', __('seller.listing_published'));
    }

    public function unpublish(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);
        abort_unless($listing->status === ListingStatus::Active, 403);

        $listing->forceFill([
            'status' => ListingStatus::Draft,
            'status_reason' => null,
            'suspended_at' => null,
        ])->save();

        return back()->with('status', __('seller.listing_unpublished'));
    }

    public function destroy(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);
        abort_unless(in_array($listing->status, [ListingStatus::Draft, ListingStatus::Rejected], true), 403);

        foreach ($listing->media as $media) {
            Storage::disk('public')->delete($media->path);
        }

        $listing->delete();

        return redirect()
            ->route('seller.listings.index')
            ->with('status', __('seller.listing_deleted'));
    }

    /**
     * FR-035 — a listing needs at least one photo before it can go live.
     * This is the only place a seller can supply one.
     */
    public function storeMedia(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);

        $request->validate([
            'photos' => ['required', 'array', 'max:8'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $position = ($listing->media()->max('position') ?? -1) + 1;

        foreach ($request->file('photos', []) as $file) {
            $listing->media()->create([
                'path' => $file->store('listings/'.$listing->id, 'public'),
                'position' => $position,
            ]);

            $position++;
        }

        return back()->with('status', __('seller.photos_uploaded'));
    }

    public function destroyMedia(Request $request, Listing $listing, ListingMedia $media): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);
        abort_unless($media->listing_id === $listing->id, 404);

        Storage::disk('public')->delete($media->path);
        $media->delete();

        return back()->with('status', __('seller.photo_removed'));
    }

    /** Move one photo to position 0 so it becomes the catalogue thumbnail. */
    public function coverMedia(Request $request, Listing $listing, ListingMedia $media): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);
        abort_unless($media->listing_id === $listing->id, 404);

        $listing->media()->orderBy('position')->get()
            ->reject(fn (ListingMedia $m) => $m->id === $media->id)
            ->values()
            ->each(fn (ListingMedia $m, int $i) => $m->update(['position' => $i + 1]));

        $media->update(['position' => 0]);

        return back()->with('status', __('seller.photo_cover_updated'));
    }

    /**
     * FR-020 — only categories whose mandatory credentials the seller can
     * actually prove today. Showing an unreachable category is how you get a
     * seller who fills in a long form and then finds out they can't publish.
     */
    private function sellableCategories(Request $request)
    {
        $held = $request->user()->approvedCredentialCodes();

        return Category::active()
            ->where('is_leaf', true)
            ->orderBy('path')
            ->get()
            ->map(function (Category $category) use ($held) {
                $required = $category->mandatoryCredentialCodes();
                $missing = $required->reject(fn ($code) => $held->contains($code))->values();

                return [
                    'category' => $category,
                    'eligible' => $missing->isEmpty(),
                    'missing' => $missing,
                ];
            });
    }

    private function validateListing(Request $request, Category $category): array
    {
        $rules = [
            'title' => ['required', 'array'],
            'title.*' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'array'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:24'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'availability_model' => ['required', 'in:in_stock,made_to_order,limited_batch,seasonal'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'season_start' => ['nullable', 'date'],
            'season_end' => ['nullable', 'date', 'after_or_equal:season_start'],
            'lot_number' => ['nullable', 'string', 'max:64'],
            'green' => ['nullable', 'array'],
            'attributes' => ['nullable', 'array'],
        ];

        // FR-012 — the category's own fields become validation rules.
        foreach ($category->effectiveFields() as $field) {
            $rules['attributes.'.$field->key] = $field->validationRules();
        }

        // FR-014 — reject an over-cap price at the form, with a message that
        // explains why rather than just refusing.
        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($v) use ($request, $category) {
            $cap = $category->activePriceCap();

            if (! $cap) {
                return;
            }

            $price = Money::fromDinars((float) $request->input('price', 0));
            $underContract = (bool) $request->input('attributes.under_exploitation_contract', false);
            $ceiling = $cap->ceilingFor($underContract);

            if ($price > $ceiling) {
                $v->errors()->add('price', __('publishing.price_above_cap', [
                    'ceiling' => Money::format($ceiling),
                    'unit' => $cap->unit,
                ]));
            }
        });

        return $validator->validate();
    }

    /** The validated form payload, shaped into Listing attributes. */
    private function attributesFromValidated(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? [],
            'price' => Money::fromDinars($data['price']),
            'unit' => $data['unit'],
            'stock' => $data['stock'] ?? 0,
            'min_order_qty' => $data['min_order_qty'] ?? 1,
            'availability_model' => $data['availability_model'],
            'lead_time_days' => $data['lead_time_days'] ?? 0,
            'season_start' => $data['season_start'] ?? null,
            'season_end' => $data['season_end'] ?? null,
            'lot_number' => $data['lot_number'] ?? null,
            'attribute_values' => $data['attributes'] ?? [],
        ];
    }
}

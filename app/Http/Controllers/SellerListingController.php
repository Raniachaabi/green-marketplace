<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\GreenAttribute;
use App\Models\Listing;
use App\Services\Publishing\PublishingGate;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('seller.listings.index', [
            'listings' => $request->user()->listings()
                ->with('category')
                ->latest()
                ->paginate(20),
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
        ]);
    }

    public function edit(Request $request, Listing $listing): View
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);

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
            'violations' => $result->violations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::findOrFail($request->input('category_id'));

        $data = $this->validateListing($request, $category);

        $listing = new Listing([
            'seller_user_id' => $request->user()->id,
            'category_id' => $category->id,
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
            'status' => ListingStatus::Draft,
        ]);

        $listing->save();

        if (! empty($data['green'])) {
            $listing->greenAttributes()->sync($data['green']);
        }

        return redirect()
            ->route('seller.listings.edit', $listing)
            ->with('status', __('seller.listing_saved'));
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
}

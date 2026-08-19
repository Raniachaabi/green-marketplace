<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\AgreementAcceptance;
use App\Services\Cart;
use App\Services\DeliveryQuoter;
use App\Services\OrderPlacer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly Cart $cart,
        private readonly DeliveryQuoter $quoter,
        private readonly OrderPlacer $placer,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();
        $lines = $this->cart->lines();
        $categories = $lines->map(fn ($l) => $l['listing']->category)->filter()->unique('id');

        $default = $addresses->first();

        return view('checkout.show', [
            'lines' => $lines,
            'subtotal' => $this->cart->subtotal(),
            'addresses' => $addresses,
            'deliveryOptions' => $default
                ? $this->quoter->optionsFor($default->governorate, $categories)
                : collect(),
            'methods' => collect(PaymentMethod::cases())
                ->filter(fn (PaymentMethod $m) => $m->isAvailableAtLaunch()),
            'buyerAgreement' => AgreementVersion::current('buyer'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:1000'],
            // FR-103 — terms acceptance is recorded, versioned and timestamped.
            'accept_terms' => ['accepted'],
        ]);

        $address = Address::where('user_id', $request->user()->id)
            ->findOrFail($validated['address_id']);

        $method = PaymentMethod::tryFrom($validated['payment_method']) ?? PaymentMethod::Cod;

        abort_unless($method->isAvailableAtLaunch(), 422);

        $this->recordTermsAcceptance($request);

        try {
            $order = $this->placer->place($request->user(), $address, $method, $validated['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', __('checkout.placed'));
    }

    private function recordTermsAcceptance(Request $request): void
    {
        $version = AgreementVersion::current('buyer');

        if (! $version) {
            return;
        }

        AgreementAcceptance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'agreement_version_id' => $version->id,
            ],
            [
                'accepted_at' => now(),
                'ip' => $request->ip(),
            ],
        );
    }
}

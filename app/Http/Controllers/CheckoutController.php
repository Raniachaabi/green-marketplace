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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
        $method = PaymentMethod::tryFrom((string) $request->input('payment_method')) ?? PaymentMethod::Cod;

        $validated = $request->validate([
            'address_id' => ['required', 'uuid'],
            // An unrecognised value must not silently fall back to COD —
            // it belongs in $method above only as a best guess for the
            // proof-file rule below; this is what actually rejects it.
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            // A bank transfer needs proof before it can be confirmed —
            // required only for that method, never for COD.
            'proof' => [
                $method === PaymentMethod::Transfer ? 'required' : 'nullable',
                'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120',
            ],
            // FR-103 — terms acceptance is recorded, versioned and timestamped.
            'accept_terms' => ['accepted'],
        ]);

        $address = Address::where('user_id', $request->user()->id)
            ->findOrFail($validated['address_id']);

        abort_unless($method->isAvailableAtLaunch(), 422);

        $this->recordTermsAcceptance($request);

        // NFR-08 — same reasoning as credential documents: never the public disk.
        $proofPath = $request->hasFile('proof')
            ? $request->file('proof')->store('', 'payment_proofs')
            : null;

        try {
            $order = $this->placer->place($request->user(), $address, $method, $validated['note'] ?? null, $proofPath);
        } catch (RuntimeException $e) {
            // The order never got created — don't leave the uploaded proof behind.
            if ($proofPath) {
                Storage::disk('payment_proofs')->delete($proofPath);
            }

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

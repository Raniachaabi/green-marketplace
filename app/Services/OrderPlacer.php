<?php

namespace App\Services;

use App\Enums\AvailabilityModel;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns a cart into an order (FR-080 to FR-092).
 *
 * Three things here are worth reading closely:
 *
 *  1. Prices and titles are SNAPSHOT onto the order line. A seller editing
 *     their listing tomorrow must not change what a buyer already bought.
 *  2. Stock is decremented inside the same transaction, with a conditional
 *     update so two simultaneous checkouts cannot oversell the last unit.
 *  3. One shipment per seller, with the carrier's handling flags derived from
 *     the category rules — that is what makes a multi-seller order shippable.
 */
class OrderPlacer
{
    public function __construct(
        private readonly Cart $cart,
        private readonly DeliveryQuoter $quoter,
        private readonly DocumentNumberGenerator $numbers,
    ) {}

    public function place(
        User $buyer,
        Address $address,
        PaymentMethod $method = PaymentMethod::Cod,
        ?string $note = null,
        ?string $proofPath = null,
    ): Order {
        $lines = $this->cart->lines()->where('available', true);

        if ($lines->isEmpty()) {
            throw new RuntimeException(__('checkout.empty_cart'));
        }

        return DB::transaction(function () use ($buyer, $address, $method, $note, $proofPath, $lines) {
            $subtotal = (int) $lines->sum('line_total');

            $categories = $lines->map(fn ($l) => $l['listing']->category)->filter()->unique('id');
            $delivery = $this->quoter->cheapest($address->governorate, $categories);
            $deliveryTotal = (int) ($delivery['price'] ?? 0);

            // VAT is shown as included in the displayed price, which is the
            // Tunisian retail convention; the line is extracted for the
            // invoice rather than added on top.
            $rate = (float) config('marketplace.vat_rate');
            $vat = (int) round(($subtotal + $deliveryTotal) * $rate / (100 + $rate));

            $order = Order::create([
                'number' => $this->orderNumber(),
                'buyer_user_id' => $buyer->id,
                'address_id' => $address->id,
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'delivery_total' => $deliveryTotal,
                'vat_total' => $vat,
                'total' => $subtotal + $deliveryTotal,
                'payment_method' => $method,
                'shipping_snapshot' => $address->toSnapshot(),
                'buyer_note' => $note,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $this->createLine($order, $line);
            }

            $this->createShipments($order, $lines, $delivery);
            $this->createPayment($order, $method, $proofPath);
            $this->issueInvoice($order);

            AuditLog::record('order.placed', $order, [
                'total' => $order->total,
                'lines' => $lines->count(),
                'method' => $method->value,
            ], $buyer);

            $this->cart->clear();

            return $order->fresh(['lines', 'shipments', 'payments', 'documents']);
        });
    }

    private function createLine(Order $order, array $line): OrderLine
    {
        /** @var Listing $listing */
        $listing = $line['listing'];
        $qty = (float) $line['qty'];

        // Conditional decrement: the WHERE clause is the lock. If another
        // checkout took the last units between our read and this write, the
        // update affects zero rows and we refuse rather than oversell.
        if ($listing->availability_model->tracksStock()) {
            $affected = Listing::whereKey($listing->id)
                ->where('stock', '>=', $qty)
                ->update(['stock' => DB::raw('stock - '.(int) ceil($qty))]);

            if ($affected === 0) {
                throw new RuntimeException(
                    __('checkout.stock_gone', ['item' => $listing->name()])
                );
            }
        }

        return OrderLine::create([
            'order_id' => $order->id,
            'listing_id' => $listing->id,
            'seller_user_id' => $listing->seller_user_id,
            'seller_org_id' => $listing->seller_org_id,
            'title_snapshot' => $listing->title,
            'unit' => $listing->unit,
            'qty' => $qty,
            'unit_price' => (int) $listing->price,
            'line_total' => (int) $line['line_total'],
            // Copied at purchase time — this is what a recall is run against.
            'lot_number' => $listing->lot_number,
            'status' => 'pending',
        ]);
    }

    /** FR-092 — one shipment per seller, not one per order. */
    private function createShipments(Order $order, Collection $lines, ?array $delivery): void
    {
        $bySeller = $lines->groupBy(
            fn ($line) => $line['listing']->seller_org_id ?: $line['listing']->seller_user_id
        );

        $codPerShipment = $order->payment_method === PaymentMethod::Cod
            ? (int) floor($order->total / max(1, $bySeller->count()))
            : 0;

        foreach ($bySeller as $sellerLines) {
            $first = $sellerLines->first()['listing'];
            $categories = $sellerLines->map(fn ($l) => $l['listing']->category)->filter()->unique('id');

            Shipment::create([
                'order_id' => $order->id,
                'seller_user_id' => $first->seller_user_id,
                'seller_org_id' => $first->seller_org_id,
                'carrier_code' => $delivery['zone']->carrier_code ?? null,
                'cod_amount' => $codPerShipment,
                'handling_flags' => $this->quoter->handlingFlags($categories),
                'method' => $delivery ? 'delivery' : 'pickup',
                'status' => 'pending',
            ]);

            $first->sellerUser?->notify(new NewOrderReceived($order));
        }
    }

    private function createPayment(Order $order, PaymentMethod $method, ?string $proofPath = null): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => $method,
            'amount' => $order->total,
            'proof_path' => $proofPath,
            'status' => match ($method) {
                PaymentMethod::Cod => 'pending_cod',
                PaymentMethod::Transfer => 'awaiting_confirmation',
                default => 'pending',
            },
        ]);
    }

    /** FR-088 — every order gets a properly numbered invoice. */
    private function issueInvoice(Order $order): Document
    {
        $numbering = $this->numbers->next('facture');

        return Document::create([
            'order_id' => $order->id,
            'type' => 'facture',
            'number' => $numbering['number'],
            'sequence' => $numbering['sequence'],
            'year' => $numbering['year'],
            'payload' => [
                'subtotal' => $order->subtotal,
                'delivery' => $order->delivery_total,
                'vat_rate' => config('marketplace.vat_rate'),
                'vat_total' => $order->vat_total,
                'total' => $order->total,
                'total_formatted' => Money::format((int) $order->total),
            ],
            'issued_at' => now(),
        ]);
    }

    private function orderNumber(): string
    {
        return 'GM-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}

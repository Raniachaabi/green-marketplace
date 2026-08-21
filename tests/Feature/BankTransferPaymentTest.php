<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class BankTransferPaymentTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function checkoutSetup(): array
    {
        $buyer = $this->seller('Buyer');
        $address = Address::create([
            'user_id' => $buyer->id, 'contact_name' => 'Buyer', 'contact_phone' => '1', 'governorate' => 'tunis',
        ]);
        $carrier = Carrier::create(['code' => 'partner', 'name' => 'Partner']);
        DeliveryZone::create([
            'carrier_code' => $carrier->code, 'governorate' => 'tunis',
            'base_price' => Money::fromDinars(7), 'lead_time_days' => 1,
        ]);
        AgreementVersion::create([
            'type' => 'buyer', 'version' => '1.0', 'title' => ['fr' => 'CGV'], 'effective_from' => now()->subDay(),
        ]);
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $buyer->refresh();

        return [$buyer, $address, $listing];
    }

    public function test_a_bank_transfer_requires_a_proof_file(): void
    {
        Storage::fake('payment_proofs');
        [$buyer, $address, $listing] = $this->checkoutSetup();

        $this->actingAs($buyer)->post(route('cart.add', $listing));

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'transfer',
            'accept_terms' => '1',
        ])->assertSessionHasErrors('proof');
    }

    public function test_a_bank_transfer_with_proof_creates_an_awaiting_confirmation_payment(): void
    {
        Storage::fake('payment_proofs');
        [$buyer, $address, $listing] = $this->checkoutSetup();

        $this->actingAs($buyer)->post(route('cart.add', $listing));

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'transfer',
            'accept_terms' => '1',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ])->assertRedirect();

        $payment = Payment::first();

        $this->assertSame(PaymentStatus::AwaitingConfirmation, $payment->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('payment_proofs')->assertExists($payment->proof_path);
    }

    public function test_cash_on_delivery_does_not_require_proof(): void
    {
        [$buyer, $address, $listing] = $this->checkoutSetup();

        $this->actingAs($buyer)->post(route('cart.add', $listing));

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'cod',
            'accept_terms' => '1',
        ])->assertRedirect();

        $this->assertSame(PaymentStatus::PendingCod, Payment::first()->status);
    }

    public function test_checkout_rejects_an_unrecognised_payment_method_instead_of_defaulting_to_cod(): void
    {
        [$buyer, $address, $listing] = $this->checkoutSetup();

        $this->actingAs($buyer)->post(route('cart.add', $listing));

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'bitcoin',
            'accept_terms' => '1',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Payment::count());
    }

    public function test_an_admin_can_confirm_a_payment_via_the_admin_action_layer(): void
    {
        Storage::fake('payment_proofs');
        [$buyer, $address, $listing] = $this->checkoutSetup();

        $this->actingAs($buyer)->post(route('cart.add', $listing));
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'transfer',
            'accept_terms' => '1',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ]);

        $payment = Payment::first();
        $payment->forceFill(['status' => PaymentStatus::Paid, 'paid_at' => now()])->save();

        $this->assertTrue($payment->fresh()->isSettled());
    }

    public function test_only_an_admin_can_view_the_uploaded_proof(): void
    {
        Storage::fake('payment_proofs');
        [$buyer, $address, $listing] = $this->checkoutSetup();

        $this->actingAs($buyer)->post(route('cart.add', $listing));
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'transfer',
            'accept_terms' => '1',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ]);

        $payment = Payment::first();

        $this->actingAs($buyer)
            ->get(route('admin.payments.proof', $payment))
            ->assertForbidden();

        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.payments.proof', $payment))
            ->assertOk();
    }
}

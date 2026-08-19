<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->string('code', 32)->primary();
            $table->string('name');
            $table->boolean('supports_cod')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('api_config')->nullable();
            $table->timestamps();
        });

        // FR-090 — the buyer sees the delivery cost before checkout.
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('carrier_code', 32);
            $table->string('governorate', 32);
            $table->unsignedBigInteger('base_price');    // millimes
            $table->unsignedBigInteger('per_kg')->default(0);
            $table->integer('lead_time_days')->default(2);
            $table->boolean('cod_supported')->default(true);
            $table->timestamps();

            $table->foreign('carrier_code')->references('code')->on('carriers')->cascadeOnDelete();
            $table->unique(['carrier_code', 'governorate']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number', 32)->unique();
            $table->foreignUuid('buyer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('buyer_org_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();

            // pending | confirmed | shipped | delivered | cancelled | refunded
            $table->string('status', 24)->default('pending');

            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('delivery_total')->default(0);
            $table->unsignedBigInteger('vat_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            // cod | transfer | konnect | flouci | paymee
            $table->string('payment_method', 24)->default('cod');

            // Snapshot of the delivery address at time of order — addresses
            // change, orders must not.
            $table->json('shipping_snapshot')->nullable();

            $table->text('buyer_note')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index('buyer_user_id');
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('listing_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('seller_org_id')->nullable()->constrained('organizations')->nullOnDelete();

            // Snapshot: the listing may be edited or deleted; the order line
            // must always show what was actually bought.
            $table->json('title_snapshot');
            $table->string('unit', 24);
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');

            // Copied from the listing at purchase time. This is what a recall
            // is executed against.
            $table->string('lot_number', 64)->nullable();

            $table->string('status', 24)->default('pending');
            $table->timestamps();

            $table->index('lot_number');
            $table->index(['seller_user_id', 'status']);
        });

        // FR-092 — one order, several sellers, several parcels.
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('seller_org_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('carrier_code', 32)->nullable();
            $table->string('tracking_ref', 64)->nullable();
            $table->unsignedBigInteger('cod_amount')->default(0);
            $table->json('handling_flags')->nullable();   // fragile, perishable, live, cold_chain
            $table->string('method', 24)->default('delivery'); // delivery|pickup
            $table->string('status', 24)->default('pending');
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('carrier_code')->references('code')->on('carriers')->nullOnDelete();
            $table->index(['status', 'carrier_code']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 24);
            $table->string('provider_ref', 128)->nullable();
            $table->unsignedBigInteger('amount');
            // pending_cod | awaiting_confirmation | paid | failed | refunded
            $table->string('status', 32)->default('pending_cod');
            $table->string('proof_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('provider_ref');
        });

        // FR-088 — invoices need gapless sequential numbering. The sequence
        // lives here so it can be locked and audited.
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 24);                  // bon_commande|bon_livraison|facture|devis
            $table->string('number', 32)->unique();
            $table->integer('sequence');
            $table->integer('year');
            $table->json('payload')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'year', 'sequence'], 'documents_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('carriers');
    }
};

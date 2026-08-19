<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // THE CATEGORY ENGINE
        //
        // This is the one piece of architecture the whole marketplace hangs
        // off. A category declares what a seller must prove, what a listing
        // must contain, how it ships, and what it may cost. Adding
        // "pepiniere" or "atelier scolaire" is rows in these tables — never
        // a migration, never a deploy.
        // ---------------------------------------------------------------
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->json('name');                        // {ar,fr,en}
            $table->json('description')->nullable();
            $table->string('path')->index();             // materialised path, e.g. "intrants/semences"
            $table->boolean('is_leaf')->default(true);

            // product | service | experience | rental — decides the checkout path (FR-015)
            $table->string('listing_type', 16)->default('product');

            $table->string('icon', 48)->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });

        // FR-011 — a seller lacking a mandatory credential cannot select the category.
        Schema::create('category_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->string('credential_type_code', 48);
            $table->boolean('is_mandatory')->default(true);
            $table->json('note')->nullable();
            $table->timestamps();

            $table->foreign('credential_type_code')->references('code')->on('credential_types')->cascadeOnDelete();
            $table->unique(['category_id', 'credential_type_code'], 'cat_req_unique');
        });

        // FR-012 — the listing form renders these fields dynamically.
        Schema::create('category_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->json('label');
            $table->json('help')->nullable();
            $table->string('data_type', 24);             // string|text|number|integer|boolean|date|select|multiselect
            $table->string('unit', 24)->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('filterable')->default(false);
            $table->json('options')->nullable();         // for select/multiselect
            $table->decimal('min_value', 14, 4)->nullable();
            $table->decimal('max_value', 14, 4)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'key']);
        });

        // FR-013 — delivery constraints propagate to shipment handling.
        Schema::create('category_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_fragile')->default(false);
            $table->boolean('is_perishable')->default(false);
            $table->boolean('is_live')->default(false);      // live plants and animals
            $table->boolean('is_heavy')->default(false);
            $table->boolean('needs_cold_chain')->default(false);
            $table->integer('max_delivery_days')->nullable(); // perishables cannot travel longer than this
            $table->integer('min_lead_time_days')->default(0);
            $table->boolean('allows_group_booking')->default(false);
            $table->boolean('requires_lot_number')->default(false); // FR-036, all edible categories
            $table->timestamps();

            $table->unique('category_id');
        });

        // FR-014 — state-fixed prices are enforced as ceilings, not suggestions.
        Schema::create('price_caps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->string('season_label', 32);           // e.g. "2026/2027"
            $table->string('unit', 24);                   // quintal, kg, litre
            $table->unsignedBigInteger('max_price');      // millimes
            $table->decimal('contract_surcharge_pct', 5, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_ref')->nullable();     // link to the ministry publication
            $table->timestamps();

            $table->index(['category_id', 'effective_from', 'effective_to'], 'price_caps_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_caps');
        Schema::dropIfExists('category_rules');
        Schema::dropIfExists('category_fields');
        Schema::dropIfExists('category_requirements');
        Schema::dropIfExists('categories');
    }
};

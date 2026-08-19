<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('green_attributes', function (Blueprint $table) {
            $table->string('code', 48)->primary();
            $table->json('label');
            $table->string('icon', 48)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Either an individual or an organization sells this — never both,
            // never neither. Enforced by a CHECK constraint below.
            $table->foreignUuid('seller_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('seller_org_id')->nullable()->constrained('organizations')->cascadeOnDelete();

            $table->foreignUuid('category_id')->constrained()->restrictOnDelete();

            $table->string('slug')->unique();
            $table->json('title');                       // {ar,fr,en}
            $table->json('description')->nullable();

            $table->unsignedBigInteger('price');         // millimes
            $table->string('unit', 24)->default('piece');
            $table->integer('stock')->default(0);
            $table->integer('min_order_qty')->default(1);

            // in_stock | made_to_order | limited_batch | seasonal   (FR-033)
            $table->string('availability_model', 24)->default('in_stock');
            $table->integer('lead_time_days')->default(0);
            $table->date('season_start')->nullable();
            $table->date('season_end')->nullable();

            // FR-036 — required for publication in any category whose rule
            // sets requires_lot_number. This single column is what makes a
            // targeted recall possible instead of a catastrophe.
            $table->string('lot_number', 64)->nullable();

            // FR-031 — category-driven attributes. Shown relationally in the
            // ERD for readability; implemented here as one JSON column.
            // On Postgres this becomes jsonb with a GIN index (see below).
            $table->json('attribute_values')->nullable();

            $table->string('governorate', 32)->nullable();

            // draft | pending | active | suspended | rejected | archived
            $table->string('status', 24)->default('draft');
            $table->text('status_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('suspended_at')->nullable();

            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index('governorate');
            $table->index(['season_start', 'season_end']);
        });

        // Exactly one seller identity. Cheap constraint, saves hours of
        // debugging orphaned rows later.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE listings ADD CONSTRAINT listings_one_seller_chk
                CHECK ((seller_user_id IS NULL) <> (seller_org_id IS NULL))
            ");
            DB::statement("CREATE INDEX listings_attr_values_gin ON listings USING gin (attribute_values jsonb_path_ops)");
        }

        Schema::create('listing_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 16)->default('image');
            $table->json('alt')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'position']);
        });

        Schema::create('listing_green_attribute', function (Blueprint $table) {
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->string('green_attribute_code', 48);

            $table->primary(['listing_id', 'green_attribute_code'], 'listing_green_attr_pk');
            $table->foreign('green_attribute_code')->references('code')->on('green_attributes')->cascadeOnDelete();
        });

        // FR-037 — plants carry a species, and a blocklisted (invasive)
        // species cannot be listed at all.
        Schema::create('species', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('botanical_name')->unique();
            $table->json('common_name');
            $table->boolean('is_native')->default(false);
            $table->boolean('is_invasive_blocked')->default(false);
            $table->string('toxicity_level', 24)->nullable();   // none|mild|toxic
            $table->string('water_need', 24)->nullable();       // low|medium|high
            $table->string('sun_exposure', 24)->nullable();
            $table->string('hardiness', 48)->nullable();
            $table->timestamps();
        });

        Schema::create('listing_species', function (Blueprint $table) {
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('species_id')->constrained()->cascadeOnDelete();

            $table->primary(['listing_id', 'species_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_species');
        Schema::dropIfExists('species');
        Schema::dropIfExists('listing_green_attribute');
        Schema::dropIfExists('listing_media');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('green_attributes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Origin (Phase 2 §10). `governorate` already exists and is
            // populated from the seller's own registered address — never
            // free text, never guessed. This adds a finer-grained, optional
            // locality and an explicit admin-set verification tier on top
            // of it, the same way credential verification works.
            $table->string('origin_locality')->nullable()->after('governorate');
            $table->timestamp('origin_verified_at')->nullable()->after('origin_locality');
            $table->foreignUuid('origin_verified_by_admin_id')->nullable()
                ->after('origin_verified_at')->constrained('users')->nullOnDelete();

            // Product storytelling (Phase 2 §9) — all optional, all shown
            // only when the seller actually filled them in.
            $table->json('story')->nullable()->after('description');
            $table->json('production_process')->nullable()->after('story');
            $table->json('ingredients_materials')->nullable()->after('production_process');
            $table->json('packaging_info')->nullable()->after('ingredients_materials');
            $table->json('care_instructions')->nullable()->after('packaging_info');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_verified_by_admin_id');
            $table->dropColumn([
                'origin_locality', 'origin_verified_at',
                'story', 'production_process', 'ingredients_materials', 'packaging_info', 'care_instructions',
            ]);
        });
    }
};

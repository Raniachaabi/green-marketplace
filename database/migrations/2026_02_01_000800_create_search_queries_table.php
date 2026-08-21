<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 §6 — every search term actually typed on the catalogue,
        // so "recent searches" and "popular searches" are real aggregates,
        // never invented lists.
        Schema::create('search_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('term', 190);
            $table->timestamp('created_at')->useCurrent();

            $table->index('term');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};

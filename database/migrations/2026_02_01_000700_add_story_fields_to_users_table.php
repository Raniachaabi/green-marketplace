<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2 §8/§14 — "Meet the Producer" on the storefront. `bio`
        // already exists as the short intro; these are the longer,
        // structured fields the storefront redesign needs — all optional,
        // and shown only once a seller actually fills them in.
        Schema::table('users', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('avatar_path');
            $table->text('story')->nullable()->after('bio');
            $table->text('production_method')->nullable()->after('story');
            $table->text('mission')->nullable()->after('production_method');
            $table->unsignedSmallInteger('founding_year')->nullable()->after('mission');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cover_path', 'story', 'production_method', 'mission', 'founding_year']);
        });
    }
};

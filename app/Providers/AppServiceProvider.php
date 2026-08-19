<?php

namespace App\Providers;

use App\Models\Listing;
use App\Observers\ListingObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Strict mode in local development only. Deliberately NOT enabled
        // under `testing`: the domain services read relations lazily by
        // design, and a violation there would fail tests for a non-bug.
        Model::preventLazyLoading(app()->environment('local'));
        Model::preventSilentlyDiscardingAttributes(app()->environment('local'));

        Listing::observe(ListingObserver::class);

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}

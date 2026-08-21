@extends('layouts.app')
@section('title', __('seller.nav_analytics'))

@section('content')
    <x-seller-nav active="analytics" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.nav_analytics') }}</h1>

        <div class="flex flex-wrap gap-1 rounded-full border border-stone-200 bg-white p-1 text-xs dark:border-border dark:bg-card">
            @foreach(['today' => __('seller.range_today'), '7d' => __('seller.range_7d'), '30d' => __('seller.range_30d'), '3m' => __('seller.range_3m'), '12m' => __('seller.range_12m')] as $key => $label)
                <a href="{{ route('seller.analytics.index', ['range' => $key]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ $range->key === $key
                                ? 'bg-gradient-brand text-white shadow-sm'
                                : 'text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 dark:text-muted-foreground dark:hover:bg-accent' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Sales Overview --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_revenue') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format($report['revenue']) }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_orders') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-leaf-900 dark:text-foreground">{{ $report['ordersCount'] }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_aov') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format($report['aov']) }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_views') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-leaf-900 dark:text-foreground">{{ $report['totalViews'] }}</p>
            @if($report['conversionRate'] !== null)
                <p class="mt-1 text-xs text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_conversion', ['rate' => $report['conversionRate']]) }}</p>
            @endif
        </div>
    </div>

    {{-- Simple revenue bar chart — pure CSS, no charting dependency. --}}
    @if($report['dailySeries']->isNotEmpty())
        <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.sales_overview') }}</p>
            @php($max = max(1, $report['dailySeries']->max()))
            <div class="flex h-24 items-end gap-1">
                @foreach($report['dailySeries'] as $bucket => $total)
                    <div class="group relative flex-1">
                        <div class="rounded-t bg-gradient-brand" style="height: {{ max(4, round($total / $max * 96)) }}px" title="{{ $bucket }}: {{ \App\Support\Money::format((int) $total) }}"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_products') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-leaf-900 dark:text-foreground">{{ $report['activeProducts'] }} <span class="text-sm font-normal text-stone-400">/ {{ $report['totalProducts'] }}</span></p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_rating') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-leaf-900 dark:text-foreground">{{ $report['averageRating'] ?? '—' }}</p>
            <p class="mt-1 text-xs text-stone-400 dark:text-muted-foreground">{{ trans_choice('listing.review_count', $report['reviewCount'], ['count' => $report['reviewCount']]) }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card sm:col-span-2">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('seller.metric_inventory') }}</p>
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-leaf-500"></span>{{ __('seller.inventory_in_stock') }} ({{ $report['inventory']['in_stock'] }})</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('seller.inventory_low_stock') }} ({{ $report['inventory']['low_stock'] }})</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>{{ __('seller.inventory_out_of_stock') }} ({{ $report['inventory']['out_of_stock'] }})</span>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.best_selling') }}</h2>
            @forelse($report['bestSelling'] as $i => $row)
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 py-2 text-sm last:border-0 dark:border-border">
                    <span class="flex items-center gap-2 truncate">
                        <span class="shrink-0 text-stone-400">{{ $i + 1 }}.</span>
                        <span class="truncate text-leaf-900 dark:text-foreground">{{ $row['listing']->name() }}</span>
                    </span>
                    <span class="shrink-0 font-semibold text-leaf-700 dark:text-primary">{{ \App\Support\Money::format($row['revenue']) }}</span>
                </div>
            @empty
                <p class="text-sm text-stone-400 dark:text-muted-foreground">{{ __('seller.no_sales_yet') }}</p>
            @endforelse
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
            <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.low_performing') }}</h2>
            @forelse($report['lowPerforming'] as $listing)
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 py-2 text-sm last:border-0 dark:border-border">
                    <span class="truncate text-leaf-900 dark:text-foreground">{{ $listing->name() }}</span>
                    <span class="shrink-0 text-xs text-stone-400 dark:text-muted-foreground">{{ trans_choice('seller.view_count', $listing->view_count, ['count' => $listing->view_count]) }}</span>
                </div>
            @empty
                <p class="text-sm text-stone-400 dark:text-muted-foreground">{{ __('seller.no_low_performers') }}</p>
            @endforelse
        </div>
    </div>
@endsection

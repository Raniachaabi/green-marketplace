@extends('layouts.app')
@php($filteredCategory = $categories->firstWhere('slug', request('category')))
@section('title', __('common.catalogue').' — '.config('app.name'))
@section('meta_description', $filteredCategory ? $filteredCategory->name().' — '.__('common.footer_note') : __('common.footer_note'))

@section('content')
    <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
        <aside>
            <form method="get" class="sticky top-24 space-y-5 rounded-2xl border border-stone-200 bg-white p-5 shadow-card text-sm dark:border-border dark:bg-card">
                <input type="hidden" name="q" value="{{ request('q') }}">

                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">
                        {{ __('catalog.category') }}
                    </h3>
                    <select name="category"
                            class="w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                        <option value="">{{ __('catalog.all') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                                {{ $category->name() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t border-stone-100 pt-5 dark:border-border">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('catalog.governorate') }}</h3>
                    <select name="governorate"
                            class="w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                        <option value="">{{ __('catalog.anywhere') }}</option>
                        @foreach($governorates as $governorate)
                            <option value="{{ $governorate }}" @selected(request('governorate') === $governorate)>
                                {{ __('governorate.'.$governorate) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t border-stone-100 pt-5 dark:border-border">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('catalog.max_price') }}</h3>
                    <div class="relative">
                        <input type="number" name="max_price" min="0" value="{{ request('max_price') }}"
                               placeholder="{{ __('catalog.max_price_placeholder') }}"
                               class="w-full rounded-lg border-stone-300 pe-12 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                        <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-xs text-stone-400 dark:text-muted-foreground">TND</span>
                    </div>
                </div>

                <div class="border-t border-stone-100 pt-5 dark:border-border">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('catalog.values') }}</h3>
                    <div class="space-y-2">
                        @foreach($greenAttributes as $attribute)
                            <label class="flex items-center gap-2 rounded-lg px-1 py-0.5 hover:bg-leaf-50 dark:hover:bg-accent">
                                <input type="checkbox" name="green[]" value="{{ $attribute->code }}"
                                       @checked(in_array($attribute->code, (array) request('green', [])))
                                       class="rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                                <span class="text-leaf-900 dark:text-foreground">{{ $attribute->name() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-stone-100 pt-5 dark:border-border">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('catalog.sort') }}</h3>
                    <select name="sort"
                            class="w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                        <option value="newest" @selected(request('sort') === 'newest')>{{ __('catalog.newest') }}</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('catalog.price_asc') }}</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('catalog.price_desc') }}</option>
                    </select>
                </div>

                {{-- §7 — advanced filtering: real, derived signals. --}}
                <div class="border-t border-stone-100 pt-5 dark:border-border">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('catalog.min_rating') }}</h3>
                    <select name="min_rating"
                            class="w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                        <option value="">{{ __('catalog.any_rating') }}</option>
                        <option value="4.5" @selected(request('min_rating') === '4.5')>4.5+</option>
                        <option value="4" @selected(request('min_rating') === '4')>4+</option>
                        <option value="3" @selected(request('min_rating') === '3')>3+</option>
                    </select>
                </div>

                <div class="border-t border-stone-100 pt-5 dark:border-border space-y-2">
                    <label class="flex items-center gap-2 rounded-lg px-1 py-0.5 hover:bg-leaf-50 dark:hover:bg-accent">
                        <input type="checkbox" name="verified" value="1" @checked(request()->boolean('verified'))
                               class="rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                        <span class="text-leaf-900 dark:text-foreground">{{ __('catalog.verified_sellers_only') }}</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-lg px-1 py-0.5 hover:bg-leaf-50 dark:hover:bg-accent">
                        <input type="checkbox" name="origin_tn" value="1" @checked(request()->boolean('origin_tn'))
                               class="rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                        <span class="text-leaf-900 dark:text-foreground">🇹🇳 {{ __('catalog.made_in_tunisia_only') }}</span>
                    </label>
                </div>

                <button class="w-full rounded-full bg-gradient-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                    {{ __('catalog.apply') }}
                </button>

                @if(request()->except(['q', 'page']))
                    <a href="{{ route('catalog.index', array_filter(['q' => request('q')])) }}"
                       class="block text-center text-xs font-medium text-stone-500 hover:text-leaf-700 dark:text-muted-foreground dark:hover:text-primary">
                        {{ __('catalog.clear_filters') }}
                    </a>
                @endif
            </form>
        </aside>

        <div>
            {{-- §6 — recent/popular searches and seller suggestions. --}}
            @if($recentSearches->isNotEmpty() || $popularSearches->isNotEmpty() || $popularSellers->isNotEmpty())
                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
                    @foreach($recentSearches as $recent)
                        <a href="{{ route('catalog.index', ['q' => $recent]) }}"
                           class="rounded-full border border-stone-200 px-3 py-1 text-stone-600 hover:border-leaf-300 dark:border-border dark:text-muted-foreground dark:hover:border-primary/50">
                            🕘 {{ $recent }}
                        </a>
                    @endforeach
                    @foreach($popularSearches->diff($recentSearches) as $popular)
                        <a href="{{ route('catalog.index', ['q' => $popular]) }}"
                           class="rounded-full border border-stone-200 px-3 py-1 text-stone-600 hover:border-leaf-300 dark:border-border dark:text-muted-foreground dark:hover:border-primary/50">
                            🔥 {{ $popular }}
                        </a>
                    @endforeach
                    @foreach($popularSellers as $popularSeller)
                        <a href="{{ route('seller.storefront', $popularSeller->slug) }}"
                           class="rounded-full bg-leaf-50 px-3 py-1 font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            {{ $popularSeller->full_name }}
                        </a>
                    @endforeach
                </div>
            @endif

            @php($activeGreen = (array) request('green', []))
            @if(request('category') || request('governorate') || request('max_price') || count($activeGreen) || request('min_rating') || request()->boolean('verified') || request()->boolean('origin_tn'))
                <div class="mb-4 flex flex-wrap gap-2">
                    @if($cat = request('category'))
                        <a href="{{ route('catalog.index', request()->except(['category', 'page'])) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            {{ $categories->firstWhere('slug', $cat)?->name() ?? $cat }} ×
                        </a>
                    @endif
                    @if($gov = request('governorate'))
                        <a href="{{ route('catalog.index', request()->except(['governorate', 'page'])) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            {{ __('governorate.'.$gov) }} ×
                        </a>
                    @endif
                    @if($max = request('max_price'))
                        <a href="{{ route('catalog.index', request()->except(['max_price', 'page'])) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            ≤ {{ $max }} TND ×
                        </a>
                    @endif
                    @foreach($activeGreen as $code)
                        <a href="{{ route('catalog.index', request()->except(['green', 'page']) + ['green' => array_values(array_diff($activeGreen, [$code]))]) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            {{ $greenAttributes->firstWhere('code', $code)?->name() ?? $code }} ×
                        </a>
                    @endforeach
                    @if($minRating = request('min_rating'))
                        <a href="{{ route('catalog.index', request()->except(['min_rating', 'page'])) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            {{ $minRating }}+ ★ ×
                        </a>
                    @endif
                    @if(request()->boolean('verified'))
                        <a href="{{ route('catalog.index', request()->except(['verified', 'page'])) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            {{ __('catalog.verified_sellers_only') }} ×
                        </a>
                    @endif
                    @if(request()->boolean('origin_tn'))
                        <a href="{{ route('catalog.index', request()->except(['origin_tn', 'page'])) }}"
                           class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700 hover:bg-leaf-100 dark:bg-primary/15 dark:text-primary dark:hover:bg-primary/25">
                            🇹🇳 {{ __('catalog.made_in_tunisia_only') }} ×
                        </a>
                    @endif
                </div>
            @endif

            <p class="mb-5 text-sm font-medium text-stone-500 dark:text-muted-foreground">
                {{ trans_choice('catalog.results', $listings->total(), ['count' => $listings->total()]) }}
            </p>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white py-12 text-center dark:border-border dark:bg-card">
                        <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('catalog.empty') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">{{ $listings->links() }}</div>

            @if($recommendations->isNotEmpty())
                <div class="mt-10">
                    <h2 class="mb-4 text-lg font-semibold text-leaf-900 dark:text-foreground">{{ __('catalog.you_might_like') }}</h2>
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($recommendations as $listing)
                            <x-listing-card :listing="$listing" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

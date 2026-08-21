@extends('layouts.app')
@section('title', $listing->name().' — '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->translate('description') ?? $listing->formattedPrice().' · '.$listing->sellerLabel()), 160))

@section('content')
    <nav class="mb-6 flex items-center gap-1.5 text-xs text-stone-400 dark:text-muted-foreground">
        <a href="{{ route('home') }}" class="hover:text-leaf-700 dark:hover:text-primary">{{ config('app.name') }}</a>
        <span>/</span>
        <a href="{{ route('catalog.index', ['category' => $listing->category->slug]) }}" class="hover:text-leaf-700 dark:hover:text-primary">
            {{ $listing->category->name() }}
        </a>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2">
        <div class="space-y-3 lg:sticky lg:top-24 lg:self-start" x-data="{ active: 0 }">
            <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-stone-100 shadow-card dark:bg-muted">
                @if($listing->media->isNotEmpty())
                    @foreach($listing->media as $media)
                        <img src="{{ $media->url() }}" alt="{{ $listing->name() }}"
                             x-show="active === {{ $loop->index }}" x-cloak
                             class="h-full w-full object-cover">
                    @endforeach
                @else
                    <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-muted-foreground/40">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-16 w-16">
                            <path d="M4 12c0-4.5 3.5-8 8-8s8 3.5 8 8M4 12c0 1 .5 2 1.5 2h13c1 0 1.5-1 1.5-2M4 12h16"
                                  stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                        </svg>
                    </div>
                @endif
            </div>

            @if($listing->media->count() > 1)
                <div class="grid grid-cols-5 gap-2">
                    @foreach($listing->media as $media)
                        <button type="button" x-on:click="active = {{ $loop->index }}"
                                class="aspect-square overflow-hidden rounded-lg bg-stone-100 ring-2 ring-offset-2 dark:bg-muted dark:ring-offset-background"
                                :class="active === {{ $loop->index }} ? 'ring-primary' : 'ring-transparent'">
                            <img src="{{ $media->url() }}" alt="" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div>
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-leaf-600 dark:text-primary">{{ $listing->category->name() }}</p>

                    <div class="flex shrink-0 items-center gap-2">
                        @auth
                            @php($wishlisted = auth()->user()->hasWishlisted($listing->id))
                            <form method="post" action="{{ route($wishlisted ? 'wishlist.destroy' : 'wishlist.store', $listing) }}">
                                @csrf
                                @if($wishlisted) @method('delete') @endif
                                <button type="submit"
                                        class="flex items-center gap-1.5 rounded-full border border-stone-200 px-2.5 py-1 text-xs font-medium text-stone-500 hover:border-red-300 hover:text-red-600 dark:border-border dark:text-muted-foreground dark:hover:border-destructive/50 dark:hover:text-destructive">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $wishlisted ? 'currentColor' : 'none' }}"
                                         class="h-3.5 w-3.5 {{ $wishlisted ? 'text-red-500 dark:text-destructive' : '' }}">
                                        <path d="M12 20.5s-7.5-4.6-9.7-9.2C.6 7.6 2.4 4.5 5.6 4c2-.3 3.9.7 5 2.3l1.4 2 1.4-2c1.1-1.6 3-2.6 5-2.3 3.2.5 5 3.6 3.3 7.3-2.2 4.6-9.7 9.2-9.7 9.2Z"
                                              stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    </svg>
                                    {{ $wishlisted ? __('wishlist.remove') : __('wishlist.add') }}
                                </button>
                            </form>
                        @endauth

                        <div x-data="{ copied: false }" class="relative">
                            <button type="button"
                                    x-on:click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="flex items-center gap-1.5 rounded-full border border-stone-200 px-2.5 py-1 text-xs font-medium text-stone-500 hover:border-leaf-300 hover:text-leaf-700 dark:border-border dark:text-muted-foreground dark:hover:border-primary/50 dark:hover:text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3.5 w-3.5">
                                    <path d="M8.5 12.5a3 3 0 0 0 4.2.3l3-2.5a3 3 0 0 0-3.8-4.6l-1.7 1.4M15.5 11.5a3 3 0 0 0-4.2-.3l-3 2.5a3 3 0 0 0 3.8 4.6l1.6-1.4"
                                          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span x-text="copied ? '{{ __('listing.link_copied') }}' : '{{ __('listing.share') }}'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <h1 class="mt-1 text-2xl font-extrabold text-leaf-950 sm:text-3xl dark:text-foreground">{{ $listing->name() }}</h1>

                @if($listing->reviewCount() > 0)
                    <a href="#reviews" class="mt-2 flex items-center gap-1.5 text-sm text-stone-600 hover:text-leaf-700 dark:text-muted-foreground dark:hover:text-primary">
                        <span class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                     class="h-4 w-4 {{ $i <= round($listing->averageRating()) ? 'text-amber-400' : 'text-stone-200 dark:text-muted' }}">
                                    <path d="m12 2.5 2.9 6.3 6.8.7-5.1 4.6 1.5 6.7L12 17.6l-6.1 3.2 1.5-6.7-5.1-4.6 6.8-.7L12 2.5Z"/>
                                </svg>
                            @endfor
                        </span>
                        <span class="font-semibold text-leaf-900 dark:text-foreground">{{ $listing->averageRating() }}</span>
                        <span>({{ trans_choice('listing.review_count', $listing->reviewCount(), ['count' => $listing->reviewCount()]) }})</span>
                    </a>
                @endif

                <p class="mt-3 text-2xl font-bold text-leaf-800 dark:text-primary">{{ $listing->formattedPrice() }}</p>
            </div>

            @if($listing->greenAttributes->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($listing->greenAttributes as $attribute)
                        <span class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-semibold text-leaf-700 ring-1 ring-leaf-100 dark:bg-primary/15 dark:text-primary dark:ring-primary/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3 w-3">
                                <path d="M12 21c-4.5 0-8-3.5-8-8 0-6 6-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                            {{ $attribute->name() }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- FR-102 — unambiguous attribution. The platform is not the seller. --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <p class="flex items-center gap-2 text-sm">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-leaf-100 text-xs font-bold uppercase text-leaf-700 dark:bg-primary/15 dark:text-primary">
                        {{ Illuminate\Support\Str::substr($listing->sellerLabel(), 0, 1) }}
                    </span>
                    <span>
                        <span class="block text-xs text-stone-500 dark:text-muted-foreground">{{ __('listing.sold_by') }}</span>
                        @if($listing->sellerUser)
                            <a href="{{ route('seller.storefront', $listing->sellerUser->slug) }}"
                               class="font-semibold text-leaf-900 hover:text-leaf-700 hover:underline dark:text-foreground dark:hover:text-primary">
                                {{ $listing->sellerLabel() }}
                            </a>
                        @else
                            <span class="font-semibold text-leaf-900 dark:text-foreground">{{ $listing->sellerLabel() }}</span>
                        @endif
                    </span>
                </p>

                @if($badges->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($badges as $badge)
                            {{-- FR-024 — every badge links to what was actually checked. --}}
                            <a href="{{ route('badges.show', $badge->code) }}"
                               class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">
                                {{ $badge->name() }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <x-origin-badge :listing="$listing" class="mt-3" />
            </div>

            <x-green-score :result="$greenScore" />

            @if($priceCap)
                {{-- FR-014 — the ceiling is shown publicly. A constraint
                     disclosed is a trust signal; a constraint hidden is a
                     suspicion waiting to happen. --}}
                <div class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0">
                        <path d="M12 9v4m0 4h.01M10.3 3.9 2.5 17.5A1.7 1.7 0 0 0 4 20h16a1.7 1.7 0 0 0 1.5-2.5L13.7 3.9a1.7 1.7 0 0 0-3.4 0Z"
                              stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                    <span>
                        {{ __('listing.price_capped', ['ceiling' => $priceCap->formattedCeiling()]) }}
                        @if($priceCap->source_ref)
                            <a href="{{ $priceCap->source_ref }}" class="underline" target="_blank" rel="noopener">
                                {{ __('listing.source') }}
                            </a>
                        @endif
                    </span>
                </div>
            @endif

            @if($listing->translate('description'))
                <p class="whitespace-pre-line text-sm leading-relaxed text-stone-700 dark:text-muted-foreground">
                    {{ $listing->translate('description') }}
                </p>
            @endif

            @if($fields->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card dark:border-border dark:bg-card">
                    <h2 class="border-b border-stone-100 bg-stone-50/60 px-4 py-2.5 text-sm font-semibold text-leaf-900 dark:border-border dark:bg-muted/40 dark:text-foreground">
                        {{ __('listing.specifications') }}
                    </h2>
                    <dl class="divide-y divide-stone-100 text-sm dark:divide-border">
                        @foreach($fields as $field)
                            @php($value = $listing->attr($field->key))
                            @if(filled($value))
                                <div class="flex justify-between gap-4 px-4 py-2.5">
                                    <dt class="text-stone-500 dark:text-muted-foreground">{{ $field->name() }}</dt>
                                    <dd class="text-end font-medium text-leaf-900 dark:text-foreground">
                                        {{ is_bool($value) ? ($value ? __('common.yes') : __('common.no')) : $value }}
                                        {{ $field->unit }}
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Phase 2 §9 — product storytelling. Each block only renders
                 if the seller actually filled it in; nothing here is ever
                 inferred. --}}
            @if($listing->translate('story'))
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('listing.about_this_product') }}</h2>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-stone-700 dark:text-muted-foreground">{{ $listing->translate('story') }}</p>
                </div>
            @endif

            @if(! empty($listing->productionProcessSteps()))
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('listing.how_its_made') }}</h2>
                    <ol class="space-y-2 text-sm text-stone-700 dark:text-muted-foreground">
                        @foreach($listing->productionProcessSteps() as $i => $step)
                            <li class="flex items-center gap-2">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-leaf-100 text-xs font-bold text-leaf-700 dark:bg-primary/15 dark:text-primary">{{ $i + 1 }}</span>
                                {{ $step }}
                            </li>
                            @if(! $loop->last)
                                <li class="ms-3 h-3 border-s-2 border-dashed border-stone-200 dark:border-border"></li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            @endif

            @if($listing->translate('ingredients_materials'))
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('listing.ingredients_materials') }}</h2>
                    <p class="whitespace-pre-line text-sm text-stone-700 dark:text-muted-foreground">{{ $listing->translate('ingredients_materials') }}</p>
                </div>
            @endif

            @if($listing->translate('packaging_info'))
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('listing.packaging_info') }}</h2>
                    <p class="whitespace-pre-line text-sm text-stone-700 dark:text-muted-foreground">{{ $listing->translate('packaging_info') }}</p>
                </div>
            @endif

            @if($listing->translate('care_instructions'))
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('listing.care_instructions') }}</h2>
                    <p class="whitespace-pre-line text-sm text-stone-700 dark:text-muted-foreground">{{ $listing->translate('care_instructions') }}</p>
                </div>
            @endif

            @if($listing->lot_number)
                <p class="text-xs text-stone-500 dark:text-muted-foreground">{{ __('listing.lot') }}: {{ $listing->lot_number }}</p>
            @endif

            @if($listing->species->isNotEmpty())
                @foreach($listing->species->filter->isToxic() as $toxic)
                    <div class="flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-destructive/30 dark:bg-destructive/10 dark:text-destructive">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0">
                            <path d="M12 9v4m0 4h.01M10.3 3.9 2.5 17.5A1.7 1.7 0 0 0 4 20h16a1.7 1.7 0 0 0 1.5-2.5L13.7 3.9a1.7 1.7 0 0 0-3.4 0Z"
                                  stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                        {{ __('listing.toxic_warning', ['species' => $toxic->name()]) }}
                    </div>
                @endforeach
            @endif

            @if($listing->category->listing_type->usesCart())
                <form method="post" action="{{ route('cart.add', $listing) }}"
                      class="flex items-end gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-stone-500 dark:text-muted-foreground">{{ __('listing.quantity') }}</label>
                        <input type="number" name="qty" step="0.001" min="{{ $listing->min_order_qty }}"
                               value="{{ $listing->min_order_qty }}"
                               class="mt-1 w-28 rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                    </div>
                    <button class="flex-1 rounded-full bg-gradient-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                            @disabled(! $listing->isPurchasable())>
                        {{ $listing->isPurchasable() ? __('listing.add_to_cart') : __('listing.unavailable') }}
                    </button>
                </form>
            @else
                <p class="rounded-2xl bg-stone-100 p-4 text-sm text-stone-600 dark:bg-muted dark:text-muted-foreground">{{ __('listing.booking_only') }}</p>
            @endif
        </div>
    </div>

    {{-- FR-100 — every review here is tied to a delivered order line. --}}
    <section id="reviews" class="mt-16 scroll-mt-24">
        <h2 class="mb-4 text-xl font-semibold text-leaf-900 dark:text-foreground">
            {{ __('listing.reviews') }}
            @if($listing->reviewCount() > 0)
                <span class="text-base font-normal text-stone-400 dark:text-muted-foreground">({{ $listing->reviewCount() }})</span>
            @endif
        </h2>

        @if($listing->reviews->isEmpty())
            <p class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-6 text-sm text-stone-500 dark:border-border dark:bg-muted/40 dark:text-muted-foreground">
                {{ __('listing.no_reviews') }}
            </p>
        @else
            <div class="space-y-4">
                @foreach($listing->reviews as $review)
                    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-leaf-100 text-xs font-bold uppercase text-leaf-700 dark:bg-primary/15 dark:text-primary">
                                    {{ Illuminate\Support\Str::substr($review->author?->full_name ?? '?', 0, 1) }}
                                </span>
                                <span class="text-sm font-semibold text-leaf-900 dark:text-foreground">{{ $review->author?->full_name ?? __('common.unknown_seller') }}</span>
                            </div>
                            <span class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                         class="h-3.5 w-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-stone-200 dark:text-muted' }}">
                                        <path d="m12 2.5 2.9 6.3 6.8.7-5.1 4.6 1.5 6.7L12 17.6l-6.1 3.2 1.5-6.7-5.1-4.6 6.8-.7L12 2.5Z"/>
                                    </svg>
                                @endfor
                            </span>
                        </div>
                        @if($review->body)
                            <p class="mt-2 text-sm text-stone-700 dark:text-muted-foreground">{{ $review->body }}</p>
                        @endif
                        <p class="mt-2 text-xs text-stone-400 dark:text-muted-foreground/70">{{ $review->created_at->format('d/m/Y') }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if($similar->isNotEmpty())
        <section class="mt-16">
            <h2 class="mb-4 text-xl font-semibold text-leaf-900 dark:text-foreground">{{ __('listing.similar') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($similar as $item)
                    <x-listing-card :listing="$item" />
                @endforeach
            </div>
        </section>
    @endif
@endsection

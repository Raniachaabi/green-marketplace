@extends('layouts.app')
@section('title', $listing->name())

@section('content')
    <nav class="mb-6 flex items-center gap-1.5 text-xs text-stone-400">
        <a href="{{ route('home') }}" class="hover:text-leaf-700">{{ config('app.name') }}</a>
        <span>/</span>
        <a href="{{ route('catalog.index', ['category' => $listing->category->slug]) }}" class="hover:text-leaf-700">
            {{ $listing->category->name() }}
        </a>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2">
        <div class="space-y-3 lg:sticky lg:top-24 lg:self-start">
            <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-stone-100 shadow-card">
                @if($listing->media->isNotEmpty())
                    <img src="{{ $listing->media->first()->url() }}" alt="{{ $listing->name() }}"
                         class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-stone-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-16 w-16">
                            <path d="M4 12c0-4.5 3.5-8 8-8s8 3.5 8 8M4 12c0 1 .5 2 1.5 2h13c1 0 1.5-1 1.5-2M4 12h16"
                                  stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                        </svg>
                    </div>
                @endif
            </div>

            @if($listing->media->count() > 1)
                <div class="grid grid-cols-5 gap-2">
                    @foreach($listing->media->skip(1)->take(5) as $media)
                        <div class="aspect-square overflow-hidden rounded-lg bg-stone-100">
                            <img src="{{ $media->url() }}" alt="" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-leaf-600">{{ $listing->category->name() }}</p>
                <h1 class="mt-1 text-2xl font-extrabold text-leaf-950 sm:text-3xl">{{ $listing->name() }}</h1>
                <p class="mt-3 text-2xl font-bold text-leaf-800">{{ $listing->formattedPrice() }}</p>
            </div>

            @if($listing->greenAttributes->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($listing->greenAttributes as $attribute)
                        <span class="inline-flex items-center gap-1 rounded-full bg-leaf-50 px-3 py-1 text-xs font-semibold text-leaf-700 ring-1 ring-leaf-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3 w-3">
                                <path d="M12 21c-4.5 0-8-3.5-8-8 0-6 6-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                            {{ $attribute->name() }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- FR-102 — unambiguous attribution. The platform is not the seller. --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card">
                <p class="flex items-center gap-2 text-sm">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-leaf-100 text-xs font-bold uppercase text-leaf-700">
                        {{ Illuminate\Support\Str::substr($listing->sellerLabel(), 0, 1) }}
                    </span>
                    <span>
                        <span class="block text-xs text-stone-500">{{ __('listing.sold_by') }}</span>
                        <span class="font-semibold text-leaf-900">{{ $listing->sellerLabel() }}</span>
                    </span>
                </p>

                @if($badges->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($badges as $badge)
                            {{-- FR-024 — every badge links to what was actually checked. --}}
                            <a href="{{ route('badges.show', $badge->code) }}"
                               class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100">
                                {{ $badge->name() }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($priceCap)
                {{-- FR-014 — the ceiling is shown publicly. A constraint
                     disclosed is a trust signal; a constraint hidden is a
                     suspicion waiting to happen. --}}
                <div class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
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
                <p class="whitespace-pre-line text-sm leading-relaxed text-stone-700">
                    {{ $listing->translate('description') }}
                </p>
            @endif

            @if($fields->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card">
                    <h2 class="border-b border-stone-100 bg-stone-50/60 px-4 py-2.5 text-sm font-semibold text-leaf-900">
                        {{ __('listing.specifications') }}
                    </h2>
                    <dl class="divide-y divide-stone-100 text-sm">
                        @foreach($fields as $field)
                            @php($value = $listing->attr($field->key))
                            @if(filled($value))
                                <div class="flex justify-between gap-4 px-4 py-2.5">
                                    <dt class="text-stone-500">{{ $field->name() }}</dt>
                                    <dd class="text-end font-medium text-leaf-900">
                                        {{ is_bool($value) ? ($value ? __('common.yes') : __('common.no')) : $value }}
                                        {{ $field->unit }}
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            @endif

            @if($listing->lot_number)
                <p class="text-xs text-stone-500">{{ __('listing.lot') }}: {{ $listing->lot_number }}</p>
            @endif

            @if($listing->species->isNotEmpty())
                @foreach($listing->species->filter->isToxic() as $toxic)
                    <div class="flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 p-3 text-xs text-red-800">
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
                      class="flex items-end gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-card">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-stone-500">{{ __('listing.quantity') }}</label>
                        <input type="number" name="qty" step="0.001" min="{{ $listing->min_order_qty }}"
                               value="{{ $listing->min_order_qty }}"
                               class="mt-1 w-28 rounded-lg border-stone-300 text-sm focus:border-leaf-500 focus:ring-leaf-500">
                    </div>
                    <button class="flex-1 rounded-full bg-leaf-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-leaf-700 disabled:cursor-not-allowed disabled:bg-stone-300"
                            @disabled(! $listing->isPurchasable())>
                        {{ $listing->isPurchasable() ? __('listing.add_to_cart') : __('listing.unavailable') }}
                    </button>
                </form>
            @else
                <p class="rounded-2xl bg-stone-100 p-4 text-sm text-stone-600">{{ __('listing.booking_only') }}</p>
            @endif
        </div>
    </div>
@endsection

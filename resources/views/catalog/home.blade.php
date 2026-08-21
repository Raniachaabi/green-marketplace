@extends('layouts.app')
@section('title', config('app.name'))

@php
    // Small, deterministic icon per root category slug — decorative only,
    // no dependency on the (currently unpopulated) category.icon column.
    $categoryIcons = [
        'intrants' => 'M12 3c1 2.5 1 5-1 7-2 2-2 4.5-1 7M12 3c-1 2.5-1 5 1 7 2 2 2 4.5 1 7M5 12h14',
        'terroir' => 'M4 12c0-4.5 3.5-8 8-8s8 3.5 8 8M4 12c0 1 .5 2 1.5 2h13c1 0 1.5-1 1.5-2M4 12h16',
        'artisanat' => 'M15 6a3 3 0 1 0-6 0c0 1.5 1 2.3 1 3.5V12h4V9.5c0-1.2 1-2 1-3.5ZM10 12h4v3a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-3Z',
        'vegetal' => 'M12 21V11m0 0c0-4-3-7-7-7 0 4 3 7 7 7Zm0 0c0-4 3-7 7-7 0 4-3 7-7 7Z',
        'services' => 'M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
    ];
    $defaultIcon = 'M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3Z';
@endphp

@section('content')
    {{-- Hero --}}
    <section class="relative mb-12 overflow-hidden rounded-3xl px-6 py-14 text-white sm:px-12 sm:py-20">
        <img src="{{ asset('images/hero.jpg') }}" alt=""
             class="absolute inset-0 h-full w-full object-cover" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-t from-leaf-900/90 via-leaf-900/60 to-leaf-900/20" aria-hidden="true"></div>

        <div class="relative max-w-2xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-leaf-50 ring-1 ring-white/20">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3.5 w-3.5">
                    <path d="M12 21c-4.5 0-8-3.5-8-8 0-6 6-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
                {{ config('app.name') }}
            </span>

            <h1 class="mt-5 text-3xl font-extrabold leading-tight sm:text-5xl">{{ __('home.headline') }}</h1>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-leaf-100 sm:text-lg">{{ __('home.subhead') }}</p>

            <form action="{{ route('catalog.index') }}" method="get" class="mt-8 max-w-lg">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         class="pointer-events-none absolute inset-y-0 start-4 my-auto h-[18px] w-[18px] text-stone-400">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m20 20-3.2-3.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input type="search" name="q" placeholder="{{ __('common.search_placeholder') }}"
                           class="w-full rounded-full border-0 bg-white py-3.5 ps-11 pe-28 text-sm text-leaf-900 shadow-lg placeholder:text-stone-400 focus:ring-2 focus:ring-white/70">
                    <button class="absolute inset-y-1.5 end-1.5 rounded-full bg-leaf-600 px-5 text-sm font-semibold text-white hover:bg-leaf-500">
                        {{ __('catalog.apply') }}
                    </button>
                </div>
            </form>

            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-leaf-100">
                <span class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4 text-leaf-300">
                        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ __('home.stat_verified') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4 text-leaf-300">
                        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ __('home.stat_categories', ['count' => $roots->count()]) }}
                </span>
                <span class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4 text-leaf-300">
                        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ __('home.stat_languages', ['count' => count(config('marketplace.locales', []))]) }}
                </span>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    <section class="mb-12">
        <div class="mb-5 flex items-end justify-between">
            <h2 class="text-xl font-bold text-leaf-900">{{ __('home.categories') }}</h2>
        </div>
        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($roots as $category)
                <a href="{{ route('catalog.index', ['category' => $category->slug]) }}"
                   class="group flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:border-leaf-300 hover:shadow-card-hover">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-leaf-50 text-leaf-700 transition group-hover:bg-leaf-600 group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                            <path d="{{ $categoryIcons[$category->slug] ?? $defaultIcon }}"
                                  stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="min-w-0 text-start">
                        <span class="block truncate text-sm font-semibold text-leaf-900">{{ $category->name() }}</span>
                        <span class="block text-xs text-stone-500">{{ __('home.listing_count', ['count' => $rootCounts[$category->id] ?? 0]) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    @if($inSeason->isNotEmpty())
        {{-- FR-054 — half this catalogue only exists for a few weeks a year.
             Surfacing that is both good merchandising and genuinely greener. --}}
        <section class="mb-12">
            <div class="mb-5 flex items-end justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                            <path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"
                                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h2 class="text-xl font-bold text-leaf-900">{{ __('home.in_season') }}</h2>
                </div>
                <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-leaf-700 hover:text-leaf-800">
                    {{ __('home.view_all') }} →
                </a>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($inSeason as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <div class="mb-5 flex items-end justify-between">
            <h2 class="text-xl font-bold text-leaf-900">{{ __('home.newest') }}</h2>
            <a href="{{ route('catalog.index', ['sort' => 'newest']) }}" class="text-sm font-semibold text-leaf-700 hover:text-leaf-800">
                {{ __('home.view_all') }} →
            </a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($newest as $listing)
                <x-listing-card :listing="$listing" />
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white py-12 text-center text-sm text-stone-500">
                    {{ __('home.empty') }}
                </p>
            @endforelse
        </div>
    </section>

    {{-- How it works — the credential-driven trust story, in three steps. --}}
    <section class="my-16 -mx-4 bg-stone-100/70 px-4 py-12 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <h2 class="mb-8 text-center text-xl font-bold text-leaf-900">{{ __('home.how_it_works') }}</h2>
        <div class="mx-auto grid max-w-5xl gap-6 sm:grid-cols-3">
            @foreach([
                ['icon' => 'M9 12h6M9 16h6M9 8h3M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z', 'title' => 'how_it_works_1_title', 'body' => 'how_it_works_1_body'],
                ['icon' => 'M9 12.5l2 2 4-4.5M12 3l8 4v5c0 4.5-3.4 8.4-8 9-4.6-.6-8-4.5-8-9V7l8-4Z', 'title' => 'how_it_works_2_title', 'body' => 'how_it_works_2_body'],
                ['icon' => 'M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', 'title' => 'how_it_works_3_title', 'body' => 'how_it_works_3_body'],
            ] as $step)
                <div class="rounded-2xl bg-white p-6 text-center shadow-card">
                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-leaf-50 text-leaf-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                            <path d="{{ $step['icon'] }}" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 text-sm font-semibold text-leaf-900">{{ __('home.'.$step['title']) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-500">{{ __('home.'.$step['body']) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @if($servicesAndExperiences->isNotEmpty())
        <section class="mb-12">
            <div class="mb-5 flex items-end justify-between">
                <h2 class="text-xl font-bold text-leaf-900">{{ __('home.services_experiences') }}</h2>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($servicesAndExperiences as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>
        </section>
    @endif

    @if($featuredSellers->isNotEmpty())
        <section class="mb-4">
            <div class="mb-5 flex items-end justify-between">
                <h2 class="text-xl font-bold text-leaf-900">{{ __('home.sellers') }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($featuredSellers as $seller)
                    @php
                        $isOrg = $seller instanceof \App\Models\Organization;
                        $label = $isOrg ? $seller->legal_name : $seller->full_name;
                        $sub = $isOrg ? __('organization.type.'.$seller->type) : null;
                        $bio = $isOrg ? $seller->story : $seller->bio;
                        $verified = $isOrg && $seller->verified_at;
                    @endphp
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-leaf-100 text-sm font-bold uppercase text-leaf-700">
                                {{ Illuminate\Support\Str::substr($label, 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-leaf-900">{{ $label }}</p>
                                @if($sub || $seller->governorate)
                                    <p class="truncate text-xs text-stone-500">
                                        {{ $sub }}{{ $sub && $seller->governorate ? ' · ' : '' }}{{ $seller->governorate ? __('governorate.'.$seller->governorate) : '' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        @if($bio)
                            <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-stone-500">{{ $bio }}</p>
                        @endif
                        @if($verified)
                            <span class="mt-3 inline-flex items-center gap-1 rounded-full bg-leaf-50 px-2.5 py-1 text-[11px] font-semibold text-leaf-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3 w-3">
                                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ __('home.verified_seller') }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection

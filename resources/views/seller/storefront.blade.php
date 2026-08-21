@extends('layouts.app')
@section('title', $seller->full_name.' — '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($seller->bio ?? ''), 160) ?: __('seller.storefront_listings').' — '.$seller->full_name)

@section('content')
    @if($seller->coverUrl())
        <div class="mb-4 h-40 w-full overflow-hidden rounded-2xl sm:h-56">
            <img src="{{ $seller->coverUrl() }}" alt="" class="h-full w-full object-cover">
        </div>
    @endif

    <div class="mb-6 flex flex-col items-start gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-card sm:flex-row sm:items-center sm:p-6 dark:border-border dark:bg-card">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-brand text-lg font-bold uppercase text-white sm:h-16 sm:w-16 sm:text-xl">
            {{ Illuminate\Support\Str::substr($seller->full_name, 0, 1) }}
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-lg font-extrabold text-leaf-950 sm:text-xl dark:text-foreground">{{ $seller->full_name }}</h1>
                @if($isVerified)
                    <x-verification-badge />
                @endif
            </div>

            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500 dark:text-muted-foreground">
                @if($ratingCount > 0)
                    <span class="flex items-center gap-1">
                        <x-rating :rating="$ratingAverage" />
                        <span class="font-semibold text-leaf-900 dark:text-foreground">{{ $ratingAverage }}</span>
                        ({{ $ratingCount }} {{ __('seller.storefront_reviews') }})
                    </span>
                @endif
                <span>{{ __('seller.member_since', ['date' => $seller->created_at->format('Y')]) }}</span>
                <span>{{ trans_choice('follow.follower_count', $followerCount, ['count' => $followerCount]) }}</span>
            </div>
        </div>

        @auth
            @if(auth()->id() !== $seller->id)
                <form method="post" action="{{ route($isFollowing ? 'seller.unfollow' : 'seller.follow', $seller->slug) }}" class="w-full shrink-0 sm:w-auto">
                    @csrf
                    @if($isFollowing) @method('delete') @endif
                    <button class="w-full rounded-full px-5 py-2 text-sm font-semibold transition sm:w-auto
                                    {{ $isFollowing
                                        ? 'border border-stone-300 text-stone-600 hover:bg-stone-50 dark:border-border dark:text-muted-foreground dark:hover:bg-accent'
                                        : 'bg-gradient-brand text-white shadow-sm hover:opacity-90' }}">
                        {{ $isFollowing ? __('follow.unfollow') : __('follow.follow') }}
                    </button>
                </form>
            @endif
        @endauth
    </div>

    <div x-data="{ tab: 'products' }">
        <div class="mb-6 flex gap-1 overflow-x-auto border-b border-stone-200 text-sm font-semibold dark:border-border" role="tablist">
            @foreach(['about' => 'tab_about', 'products' => 'tab_products', 'reviews' => 'tab_reviews', 'certifications' => 'tab_certifications', 'our_story' => 'tab_our_story'] as $key => $label)
                <button type="button" role="tab" @click="tab = '{{ $key }}'"
                        :aria-selected="(tab === '{{ $key }}').toString()"
                        :class="tab === '{{ $key }}' ? 'border-leaf-700 text-leaf-800 dark:border-primary dark:text-primary' : 'border-transparent text-stone-500 hover:text-leaf-700 dark:text-muted-foreground dark:hover:text-primary'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 sm:px-4">
                    {{ __('seller.'.$label) }}
                </button>
            @endforeach
        </div>

        <div x-show="tab === 'about'" x-cloak>
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-card dark:border-border dark:bg-card">
                @if($seller->bio)
                    <p class="whitespace-pre-line text-sm leading-relaxed text-stone-700 dark:text-muted-foreground">{{ $seller->bio }}</p>
                @else
                    <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.about_no_bio') }}</p>
                @endif
            </div>
        </div>

        <div x-show="tab === 'products'" x-cloak>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @empty
                    <x-empty-state :message="__('seller.storefront_no_listings')" />
                @endforelse
            </div>
            <div class="mt-8">{{ $listings->links() }}</div>
        </div>

        <div x-show="tab === 'reviews'" x-cloak class="space-y-4">
            @forelse($reviews as $review)
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <x-rating :rating="$review->rating" />
                            <span class="text-sm font-semibold text-leaf-900 dark:text-foreground">{{ $review->author->full_name }}</span>
                        </div>
                        <span class="text-xs text-stone-400 dark:text-muted-foreground/70">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    @if($reviewedListings->get($review->target_id))
                        <p class="mt-1 text-xs text-stone-500 dark:text-muted-foreground">{{ $reviewedListings->get($review->target_id)->name() }}</p>
                    @endif
                    @if($review->body)
                        <p class="mt-2 text-sm leading-relaxed text-stone-700 dark:text-muted-foreground">{{ $review->body }}</p>
                    @endif
                </div>
            @empty
                <x-empty-state :message="__('seller.no_reviews')" />
            @endforelse
            <div class="mt-4">{{ $reviews->links() }}</div>
        </div>

        <div x-show="tab === 'certifications'" x-cloak>
            @if($badges->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($badges as $badge)
                        <a href="{{ route('badges.show', $badge->code) }}"
                           class="rounded-full bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">
                            {{ $badge->name() }}
                        </a>
                    @endforeach
                </div>
            @else
                <x-empty-state :message="__('seller.no_certifications')" />
            @endif
        </div>

        {{-- Phase 2 §8/§14 — "Meet the Producer", shown only if the seller filled it in. --}}
        <div x-show="tab === 'our_story'" x-cloak>
            @if($seller->hasStory())
                <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-4 text-lg font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.meet_the_producer') }}</h2>

                    <div class="mb-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-stone-500 dark:text-muted-foreground">
                        @if($seller->yearsActive() !== null)
                            <span>{{ trans_choice('seller.years_active', $seller->yearsActive(), ['count' => $seller->yearsActive()]) }}</span>
                        @endif
                        @if($seller->production_method)
                            <span>{{ $seller->production_method }}</span>
                        @endif
                    </div>

                    @if($seller->story)
                        <p class="mb-4 whitespace-pre-line text-sm leading-relaxed text-stone-700 dark:text-muted-foreground">{{ $seller->story }}</p>
                    @endif

                    @if($seller->mission)
                        <div class="rounded-xl bg-leaf-50 p-4 text-sm text-leaf-800 dark:bg-primary/10 dark:text-primary">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide">{{ __('seller.mission') }}</p>
                            {{ $seller->mission }}
                        </div>
                    @endif
                </div>
            @else
                <x-empty-state :message="__('seller.no_story')" />
            @endif
        </div>
    </div>
@endsection

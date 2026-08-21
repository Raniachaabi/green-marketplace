@extends('layouts.app')
@section('title', __('seller.my_listings'))

@section('content')
    <x-seller-nav active="listings" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.my_listings') }}</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">
                {{ trans_choice('seller.listing_count', $total, ['count' => $total]) }}
            </p>
        </div>
        <a href="{{ route('seller.listings.create') }}"
           class="rounded-full bg-gradient-brand px-5 py-2.5 text-sm font-semibold text-white shadow-card hover:opacity-90">
            {{ __('seller.new_listing') }}
        </a>
    </div>

    {{-- Status filter tabs, each carrying its own live count. --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('seller.listings.index') }}"
           class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                  {{ $status === ''
                        ? 'bg-leaf-900 text-white dark:bg-primary dark:text-primary-foreground'
                        : 'bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-muted dark:text-muted-foreground dark:hover:bg-accent' }}">
            {{ __('seller.filter_all') }} · {{ $total }}
        </a>
        @foreach(\App\Enums\ListingStatus::cases() as $case)
            @php($count = $counts->get($case->value, 0))
            <a href="{{ route('seller.listings.index', ['status' => $case->value]) }}"
               class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                      {{ $status === $case->value
                            ? 'bg-leaf-900 text-white dark:bg-primary dark:text-primary-foreground'
                            : 'bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-muted dark:text-muted-foreground dark:hover:bg-accent' }}">
                {{ $case->label() }} · {{ $count }}
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($listings as $listing)
            <div class="flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card dark:border-border dark:bg-card">
                <div class="relative aspect-[4/3] bg-stone-100 dark:bg-muted">
                    @if($listing->media->isNotEmpty())
                        <img src="{{ $listing->media->first()->url() }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-muted-foreground/40">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-9 w-9">
                                <path d="M4 12c0-4.5 3.5-8 8-8s8 3.5 8 8M4 12c0 1 .5 2 1.5 2h13c1 0 1.5-1 1.5-2M4 12h16"
                                      stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            </svg>
                        </div>
                    @endif
                    <div class="absolute start-2 top-2">
                        <x-status-badge :status="$listing->status" />
                    </div>
                </div>

                <div class="flex flex-1 flex-col gap-1 p-4">
                    <p class="line-clamp-1 font-medium text-leaf-900 dark:text-foreground">{{ $listing->name() }}</p>
                    <p class="text-xs text-stone-500 dark:text-muted-foreground">
                        {{ $listing->category?->name() }} · {{ $listing->formattedPrice() }}
                    </p>

                    @if($listing->status_reason)
                        <p class="mt-1 line-clamp-2 text-xs text-amber-700 dark:text-amber-400">
                            {{ $listing->status_reason === 'edited_after_publication' ? __('seller.status_reason_edited') : $listing->status_reason }}
                        </p>
                    @endif

                    <div class="mt-auto flex items-center gap-3 pt-3">
                        <a href="{{ route('seller.listings.edit', $listing) }}"
                           class="text-xs font-semibold text-leaf-700 hover:underline dark:text-primary">
                            {{ __('seller.manage') }}
                        </a>

                        @if($listing->status !== \App\Enums\ListingStatus::Active)
                            <form method="post" action="{{ route('seller.listings.publish', $listing) }}">
                                @csrf
                                <button class="text-xs font-semibold text-stone-500 hover:text-leaf-700 dark:text-muted-foreground dark:hover:text-primary">
                                    {{ __('seller.publish') }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('catalog.show', $listing->slug) }}" target="_blank" rel="noopener"
                               class="text-xs font-semibold text-stone-500 hover:text-leaf-700 dark:text-muted-foreground dark:hover:text-primary">
                                {{ __('seller.view_live') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.no_listings') }}</p>
                <a href="{{ route('seller.listings.create') }}"
                   class="mt-3 inline-block rounded-full bg-gradient-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    {{ __('seller.new_listing') }}
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $listings->links() }}</div>
@endsection

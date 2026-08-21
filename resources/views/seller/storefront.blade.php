@extends('layouts.app')
@section('title', $seller->full_name.' — '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($seller->bio ?? ''), 160) ?: __('seller.storefront_listings').' — '.$seller->full_name)

@section('content')
    <div class="mb-8 flex flex-col items-start gap-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-card sm:flex-row sm:items-center dark:border-border dark:bg-card">
        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-brand text-xl font-bold uppercase text-white">
            {{ Illuminate\Support\Str::substr($seller->full_name, 0, 1) }}
        </span>

        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-extrabold text-leaf-950 dark:text-foreground">{{ $seller->full_name }}</h1>

            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500 dark:text-muted-foreground">
                @if($ratingCount > 0)
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 text-amber-400">
                            <path d="m12 2.5 2.9 6.3 6.8.7-5.1 4.6 1.5 6.7L12 17.6l-6.1 3.2 1.5-6.7-5.1-4.6 6.8-.7L12 2.5Z"/>
                        </svg>
                        <span class="font-semibold text-leaf-900 dark:text-foreground">{{ $ratingAverage }}</span>
                        ({{ $ratingCount }} {{ __('seller.storefront_reviews') }})
                    </span>
                @endif
                <span>{{ __('seller.member_since', ['date' => $seller->created_at->format('Y')]) }}</span>
            </div>

            @if($seller->bio)
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-600 dark:text-muted-foreground">{{ $seller->bio }}</p>
            @endif

            @if($badges->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($badges as $badge)
                        <a href="{{ route('badges.show', $badge->code) }}"
                           class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">
                            {{ $badge->name() }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <h2 class="mb-4 text-lg font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.storefront_listings') }}</h2>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($listings as $listing)
            <x-listing-card :listing="$listing" />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.storefront_no_listings') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $listings->links() }}</div>
@endsection

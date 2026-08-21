@extends('layouts.app')
@section('title', __('follow.following_title'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('follow.following_title') }}</h1>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($sellers as $seller)
            <div class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <a href="{{ route('seller.storefront', $seller->slug) }}" class="flex min-w-0 flex-1 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-brand text-sm font-bold uppercase text-white">
                        {{ \Illuminate\Support\Str::substr($seller->full_name, 0, 1) }}
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-medium text-leaf-900 dark:text-foreground">{{ $seller->full_name }}</span>
                        <span class="block text-xs text-stone-500 dark:text-muted-foreground">{{ trans_choice('follow.follower_count', $seller->followerCount(), ['count' => $seller->followerCount()]) }}</span>
                    </span>
                </a>
                <form method="post" action="{{ route('seller.unfollow', $seller->slug) }}">
                    @csrf
                    @method('delete')
                    <button class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-medium text-stone-600 hover:bg-stone-50 dark:border-border dark:text-muted-foreground dark:hover:bg-accent">
                        {{ __('follow.unfollow') }}
                    </button>
                </form>
            </div>
        @empty
            <x-empty-state :message="__('follow.empty')" :action-label="__('cart.browse')" :action-href="route('catalog.index')" />
        @endforelse
    </div>

    <div class="mt-8">{{ $sellers->links() }}</div>
@endsection

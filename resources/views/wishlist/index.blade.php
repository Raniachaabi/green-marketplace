@extends('layouts.app')
@section('title', __('wishlist.title'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('wishlist.title') }}</h1>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($listings as $listing)
            <x-listing-card :listing="$listing" />
        @empty
            <x-empty-state :message="__('wishlist.empty')" :action-label="__('cart.browse')" :action-href="route('catalog.index')" />
        @endforelse
    </div>

    <div class="mt-8">{{ $listings->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', __('wishlist.title'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('wishlist.title') }}</h1>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($listings as $listing)
            <x-listing-card :listing="$listing" />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('wishlist.empty') }}</p>
                <a href="{{ route('catalog.index') }}"
                   class="mt-3 inline-block rounded-full bg-gradient-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    {{ __('cart.browse') }}
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $listings->links() }}</div>
@endsection

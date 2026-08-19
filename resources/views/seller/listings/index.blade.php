@extends('layouts.app')
@section('title', __('seller.my_listings'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ __('seller.my_listings') }}</h1>
        <a href="{{ route('seller.listings.create') }}"
           class="rounded-lg bg-leaf-600 px-4 py-2 text-sm font-medium text-white hover:bg-leaf-700">
            {{ __('seller.new_listing') }}
        </a>
    </div>

    <div class="space-y-2">
        @forelse($listings as $listing)
            <div class="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
                <div>
                    <p class="font-medium">{{ $listing->name() }}</p>
                    <p class="text-xs text-stone-500">
                        {{ $listing->category?->name() }} · {{ $listing->formattedPrice() }}
                    </p>
                    @if($listing->status_reason)
                        <p class="mt-1 text-xs text-amber-700">{{ $listing->status_reason }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-stone-100 px-3 py-1 text-xs">{{ $listing->status->label() }}</span>

                    @if($listing->status !== \App\Enums\ListingStatus::Active)
                        <form method="post" action="{{ route('seller.listings.publish', $listing) }}">
                            @csrf
                            <button class="text-xs text-leaf-700 underline">{{ __('seller.publish') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-stone-500">{{ __('seller.no_listings') }}</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $listings->links() }}</div>
@endsection

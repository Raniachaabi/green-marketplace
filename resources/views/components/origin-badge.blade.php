@props(['listing'])

@if($listing->governorate)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium '
        .($listing->isOriginVerified()
            ? 'bg-leaf-50 text-leaf-700 dark:bg-primary/15 dark:text-primary'
            : 'bg-stone-100 text-stone-600 dark:bg-muted dark:text-muted-foreground')]) }}>
        <span aria-hidden="true">🇹🇳</span>
        {{ __('listing.made_in_tunisia') }}
        @if($listing->origin_locality)
            — {{ $listing->origin_locality }}
        @else
            — {{ __('governorate.'.$listing->governorate) }}
        @endif
        @if($listing->isOriginVerified())
            <span title="{{ __('listing.origin_verified') }}" aria-hidden="true">✓</span>
        @endif
    </div>
@endif

@props(['active'])

@php
    $tabs = [
        'listings' => ['route' => route('seller.listings.index'), 'label' => __('seller.nav_listings')],
        'create' => ['route' => route('seller.listings.create'), 'label' => __('seller.nav_new')],
        'onboarding' => ['route' => route('seller.onboarding'), 'label' => __('seller.nav_documents')],
    ];
@endphp

<nav class="mb-6 flex items-center gap-1 overflow-x-auto rounded-full border border-stone-200 bg-white p-1 text-sm dark:border-border dark:bg-card">
    @foreach($tabs as $key => $tab)
        <a href="{{ $tab['route'] }}"
           class="shrink-0 rounded-full px-4 py-2 font-medium transition
                  {{ $active === $key
                        ? 'bg-gradient-brand text-white shadow-sm'
                        : 'text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>

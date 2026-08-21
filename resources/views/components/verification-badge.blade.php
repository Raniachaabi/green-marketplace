@props([])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-leaf-50 px-2.5 py-1 text-[11px] font-semibold text-leaf-700 dark:bg-primary/10 dark:text-primary']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3 w-3">
        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    {{ $slot->isEmpty() ? __('home.verified_seller') : $slot }}
</span>

@props(['rating', 'size' => 'h-3.5 w-3.5'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }}>
    @for($i = 1; $i <= 5; $i++)
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
             class="{{ $size }} {{ $i <= round($rating) ? 'text-amber-400' : 'text-stone-200 dark:text-muted' }}">
            <path d="m12 2.5 2.9 6.3 6.8.7-5.1 4.6 1.5 6.7L12 17.6l-6.1 3.2 1.5-6.7-5.1-4.6 6.8-.7L12 2.5Z"/>
        </svg>
    @endfor
</span>

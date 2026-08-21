@props(['status'])

@php
    $classes = match ($status->colour()) {
        'success' => 'bg-leaf-50 text-leaf-700 dark:bg-primary/15 dark:text-primary',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-700 dark:bg-destructive/10 dark:text-destructive',
        'info' => 'bg-blue-50 text-blue-700 dark:bg-secondary/15 dark:text-secondary',
        default => 'bg-stone-100 text-stone-600 dark:bg-muted dark:text-muted-foreground',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold $classes"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $status->label() }}
</span>

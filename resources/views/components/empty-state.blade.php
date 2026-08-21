@props(['message', 'actionLabel' => null, 'actionHref' => null])

<div {{ $attributes->merge(['class' => 'col-span-full rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40']) }}>
    <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ $message }}</p>

    @if($actionLabel && $actionHref)
        <a href="{{ $actionHref }}"
           class="mt-3 inline-block rounded-full bg-gradient-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
            {{ $actionLabel }}
        </a>
    @endif
</div>

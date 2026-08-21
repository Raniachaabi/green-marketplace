@props(['result'])

@if(! $result->isEmpty())
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card']) }}>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-muted-foreground">
                {{ __('green_score.title') }}
            </h2>
            <span class="text-lg font-extrabold text-leaf-700 dark:text-primary">
                {{ __('green_score.out_of', ['score' => $result->total]) }}
            </span>
        </div>

        <div class="mb-4 h-2 w-full overflow-hidden rounded-full bg-stone-100 dark:bg-muted">
            <div class="h-full rounded-full bg-gradient-brand" style="width: {{ $result->total }}%"></div>
        </div>

        <ul class="space-y-1.5 text-sm">
            @foreach($result->breakdown as $item)
                <li class="flex items-center justify-between gap-2 text-leaf-900 dark:text-foreground">
                    <span class="flex items-center gap-2">
                        <span aria-hidden="true">{{ $item['rule']->icon }}</span>
                        {{ $item['rule']->name() }}
                    </span>
                    <span class="shrink-0 font-semibold text-leaf-700 dark:text-primary">+{{ $item['points'] }}</span>
                </li>
            @endforeach
        </ul>

        <p class="mt-3 text-xs text-stone-400 dark:text-muted-foreground">{{ __('green_score.explanation') }}</p>
    </div>
@endif

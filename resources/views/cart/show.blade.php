@extends('layouts.app')
@section('title', __('common.cart'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('common.cart') }}</h1>

        @if($lines->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('cart.empty') }}</p>
                <a href="{{ route('catalog.index') }}"
                   class="mt-3 inline-block rounded-full bg-gradient-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    {{ __('cart.browse') }}
                </a>
            </div>
        @else
            <div class="space-y-8">
                @foreach($lines->groupBy(fn ($line) => $line['listing']->seller()?->id ?? 'unknown') as $sellerLines)
                    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card dark:border-border dark:bg-card">
                        <div class="flex items-center gap-2 border-b border-stone-100 bg-stone-50/60 px-4 py-2.5 text-sm font-semibold text-leaf-900 dark:border-border dark:bg-muted/40 dark:text-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4 text-stone-400 dark:text-muted-foreground">
                                <path d="M3 7l1-3h16l1 3M4 7h16v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7Zm4 4a2 2 0 0 0 4 0m0 0a2 2 0 0 0 4 0"
                                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            {{ $sellerLines->first()['listing']->sellerLabel() }}
                        </div>

                        <div class="divide-y divide-stone-100 dark:divide-border">
                            @foreach($sellerLines as $line)
                                <div class="flex items-center gap-4 p-4 {{ $line['available'] ? '' : 'opacity-60' }}">
                                    <a href="{{ route('catalog.show', $line['listing']->slug) }}"
                                       class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-muted">
                                        @if($line['listing']->media->isNotEmpty())
                                            <img src="{{ $line['listing']->media->first()->url() }}" alt=""
                                                 class="h-full w-full object-cover">
                                        @endif
                                    </a>

                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('catalog.show', $line['listing']->slug) }}"
                                           class="line-clamp-1 font-medium text-leaf-900 hover:text-leaf-700 dark:text-foreground dark:hover:text-primary">
                                            {{ $line['listing']->name() }}
                                        </a>
                                        <p class="text-xs text-stone-500 dark:text-muted-foreground">
                                            {{ \App\Support\Money::format($line['unit_price']) }} / {{ __('unit.'.$line['listing']->unit) }}
                                        </p>
                                        @unless($line['available'])
                                            <p class="mt-1 text-xs font-medium text-red-600 dark:text-destructive">{{ $line['reason'] }}</p>
                                        @endunless
                                    </div>

                                    <form method="post" action="{{ route('cart.update', $line['listing']->id) }}" class="flex items-center gap-1">
                                        @csrf @method('patch')
                                        <input type="number" name="qty" value="{{ $line['qty'] }}" step="0.001" min="0"
                                               class="w-20 rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                                        <button class="rounded-full px-2 py-1.5 text-xs font-semibold text-stone-500 hover:bg-stone-100 dark:text-muted-foreground dark:hover:bg-accent">
                                            {{ __('cart.update_action') }}
                                        </button>
                                    </form>

                                    <p class="w-24 shrink-0 text-end font-semibold text-leaf-900 dark:text-foreground">
                                        {{ \App\Support\Money::format($line['line_total']) }}
                                    </p>

                                    <form method="post" action="{{ route('cart.remove', $line['listing']->id) }}">
                                        @csrf @method('delete')
                                        <button class="rounded-full p-1.5 text-stone-400 hover:bg-red-50 hover:text-red-600 dark:text-muted-foreground dark:hover:bg-destructive/10 dark:hover:text-destructive"
                                                aria-label="{{ __('cart.remove_action') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                                                <path d="M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1l1-12"
                                                      stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex items-center justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <span class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('cart.subtotal') }}</span>
                <span class="text-lg font-semibold text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format($subtotal) }}</span>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <a href="{{ route('catalog.index') }}" class="text-sm text-leaf-700 underline dark:text-primary">
                    {{ __('cart.continue_shopping') }}
                </a>
                <a href="{{ route('checkout.show') }}"
                   class="inline-block rounded-full bg-gradient-brand px-6 py-2.5 font-semibold text-white shadow-card hover:opacity-90">
                    {{ __('cart.checkout') }}
                </a>
            </div>
        @endif
    </div>
@endsection

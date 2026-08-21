@extends('layouts.app')
@section('title', __('common.my_orders'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('common.my_orders') }}</h1>
        <a href="{{ route('addresses.index') }}" class="text-sm font-semibold text-leaf-700 hover:underline dark:text-primary">
            {{ __('account.manage_addresses') }}
        </a>
    </div>

    <div class="space-y-3">
        @forelse($orders as $order)
            <a href="{{ route('orders.show', $order) }}"
               class="flex items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-card hover:border-leaf-300 dark:border-border dark:bg-card dark:hover:border-primary/50">
                <div>
                    <p class="font-medium text-leaf-900 dark:text-foreground">{{ $order->number }}</p>
                    <p class="text-xs text-stone-500 dark:text-muted-foreground">
                        {{ $order->placed_at?->format('d/m/Y') }} —
                        {{ trans_choice('order.items', $order->lines->count(), ['count' => $order->lines->count()]) }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-end font-medium text-leaf-900 dark:text-foreground">{{ $order->formattedTotal() }}</p>
                    <x-status-badge :status="$order->status" />
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('order.none') }}</p>
                <a href="{{ route('catalog.index') }}"
                   class="mt-3 inline-block rounded-full bg-gradient-brand px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    {{ __('cart.browse') }}
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $orders->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', __('seller.nav_orders'))

@section('content')
    <x-seller-nav active="orders" />

    <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.nav_orders') }}</h1>

    <div class="space-y-3">
        @forelse($orders as $order)
            @php($mySubtotal = $order->lines->sum('line_total'))
            <a href="{{ route('seller.orders.show', $order) }}"
               class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-card hover:border-leaf-300 dark:border-border dark:bg-card dark:hover:border-primary/50">
                <div>
                    <p class="font-medium text-leaf-900 dark:text-foreground">{{ $order->number }}</p>
                    <p class="text-xs text-stone-500 dark:text-muted-foreground">
                        {{ $order->placed_at?->format('d/m/Y') }} —
                        {{ trans_choice('order.items', $order->lines->count(), ['count' => $order->lines->count()]) }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <p class="font-medium text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format((int) $mySubtotal) }}</p>
                    @if($shipment = $order->shipments->first())
                        <x-status-badge :status="$shipment->status" />
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.no_orders') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $orders->links() }}</div>
@endsection

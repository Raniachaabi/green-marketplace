@extends('layouts.app')
@section('title', __('common.my_orders'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('common.my_orders') }}</h1>

    <div class="space-y-3">
        @forelse($orders as $order)
            <a href="{{ route('orders.show', $order) }}"
               class="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4 hover:border-leaf-300">
                <div>
                    <p class="font-medium">{{ $order->number }}</p>
                    <p class="text-xs text-stone-500">
                        {{ $order->placed_at?->format('d/m/Y') }} —
                        {{ trans_choice('order.items', $order->lines->count(), ['count' => $order->lines->count()]) }}
                    </p>
                </div>
                <div class="text-end">
                    <p class="font-medium">{{ $order->formattedTotal() }}</p>
                    <p class="text-xs text-stone-500">{{ $order->status->label() }}</p>
                </div>
            </a>
        @empty
            <p class="text-sm text-stone-500">{{ __('order.none') }}</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $orders->links() }}</div>
@endsection

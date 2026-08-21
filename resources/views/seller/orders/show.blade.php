@extends('layouts.app')
@section('title', $order->number)

@section('content')
    <x-seller-nav active="orders" />

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ $order->number }}</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">{{ $order->placed_at?->format('d/m/Y H:i') }}</p>
        </div>
        <x-status-badge :status="$order->status" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card dark:border-border dark:bg-card">
                <h2 class="border-b border-stone-200 px-4 py-2 font-semibold text-leaf-900 dark:border-border dark:text-foreground">{{ __('order.items_title') }}</h2>
                <ul class="divide-y divide-stone-100 text-sm dark:divide-border">
                    @foreach($order->lines as $line)
                        <li class="flex items-center justify-between gap-4 px-4 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-muted">
                                    @if($line->listing?->media->isNotEmpty())
                                        <img src="{{ $line->listing->media->first()->url() }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="line-clamp-1 font-medium text-leaf-900 dark:text-foreground">{{ $line->title() }}</p>
                                    <p class="text-xs text-stone-500 dark:text-muted-foreground">
                                        {{ $line->qty }} × {{ \App\Support\Money::format((int) $line->unit_price) }}
                                        @if($line->lot_number)
                                            · {{ __('listing.lot') }} {{ $line->lot_number }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <p class="shrink-0 font-medium text-leaf-900 dark:text-foreground">{{ $line->formattedTotal() }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="flex justify-between border-t border-stone-100 px-4 py-3 text-sm font-semibold text-leaf-900 dark:border-border dark:text-foreground">
                    <span>{{ __('cart.subtotal') }}</span>
                    <span>{{ \App\Support\Money::format((int) $order->lines->sum('line_total')) }}</span>
                </div>
            </div>

            @if($shipment = $order->shipments->first())
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('order.shipments_title') }}</h2>
                    <form method="post" action="{{ route('seller.orders.shipment.update', [$order, $shipment]) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        @method('patch')
                        <label class="text-sm">
                            <span class="block text-stone-500 dark:text-muted-foreground">{{ __('seller.fulfillment_status') }}</span>
                            <select name="status"
                                    class="mt-1 rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                                @foreach(\App\Enums\ShipmentStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($shipment->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                            {{ __('common.save') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            @if($order->address)
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card text-sm dark:border-border dark:bg-card">
                    <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.address') }}</h2>
                    <p class="text-stone-600 dark:text-muted-foreground">
                        {{ $order->address->contact_name }} · {{ $order->address->contact_phone }}<br>
                        {{ $order->address->oneLine() }}
                    </p>
                </div>
            @endif

            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card text-sm dark:border-border dark:bg-card">
                <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.payment') }}</h2>
                <p class="text-stone-600 dark:text-muted-foreground">{{ $order->payment_method->label() }}</p>
            </div>
        </aside>
    </div>

    <a href="{{ route('seller.orders.index') }}" class="mt-6 inline-block text-sm text-leaf-700 underline dark:text-primary">
        {{ __('common.back') }}
    </a>
@endsection

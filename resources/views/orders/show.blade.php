@extends('layouts.app')
@section('title', $order->number)

@section('content')
    <h1 class="text-2xl font-semibold">{{ $order->number }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ $order->status->label() }} — {{ $order->placed_at?->format('d/m/Y H:i') }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="rounded-xl border border-stone-200 bg-white">
            <h2 class="border-b border-stone-200 px-4 py-2 font-semibold">{{ __('order.items_title') }}</h2>
            <ul class="divide-y divide-stone-100 text-sm">
                @foreach($order->lines as $line)
                    <li class="flex justify-between gap-4 px-4 py-3">
                        <div>
                            <p class="font-medium">{{ $line->title() }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $line->qty }} × {{ \App\Support\Money::format((int) $line->unit_price) }}
                                @if($line->lot_number)
                                    · {{ __('listing.lot') }} {{ $line->lot_number }}
                                @endif
                            </p>
                        </div>
                        <p class="font-medium">{{ $line->formattedTotal() }}</p>
                    </li>
                @endforeach
            </ul>
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm">
                <div class="flex justify-between"><span class="text-stone-500">{{ __('cart.subtotal') }}</span><span>{{ \App\Support\Money::format((int) $order->subtotal) }}</span></div>
                <div class="mt-1 flex justify-between"><span class="text-stone-500">{{ __('checkout.delivery') }}</span><span>{{ \App\Support\Money::format((int) $order->delivery_total) }}</span></div>
                <div class="mt-1 flex justify-between text-xs text-stone-500"><span>{{ __('order.vat_included', ['rate' => config('marketplace.vat_rate')]) }}</span><span>{{ \App\Support\Money::format((int) $order->vat_total) }}</span></div>
                <div class="mt-3 flex justify-between border-t border-stone-100 pt-3 font-semibold"><span>{{ __('order.total') }}</span><span>{{ $order->formattedTotal() }}</span></div>
            </div>

            @foreach($order->documents as $document)
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-sm">
                    <p class="font-medium">{{ __('order.document.'.$document->type) }}</p>
                    <p class="text-xs text-stone-500">{{ $document->number }}</p>
                </div>
            @endforeach
        </aside>
    </div>
@endsection

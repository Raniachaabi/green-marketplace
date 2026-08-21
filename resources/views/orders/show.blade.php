@extends('layouts.app')
@section('title', $order->number)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ $order->number }}</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">{{ $order->placed_at?->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-status-badge :status="$order->status" />
            <form method="post" action="{{ route('orders.reorder', $order) }}">
                @csrf
                <button class="rounded-full border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-600 hover:border-leaf-300 hover:text-leaf-700 dark:border-border dark:text-muted-foreground dark:hover:border-primary/50 dark:hover:text-primary">
                    {{ __('order.reorder') }}
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card dark:border-border dark:bg-card">
                <h2 class="border-b border-stone-200 px-4 py-2 font-semibold text-leaf-900 dark:border-border dark:text-foreground">{{ __('order.items_title') }}</h2>
                <ul class="divide-y divide-stone-100 dark:divide-border">
                    @foreach($order->lines as $line)
                        <li class="p-4">
                            <div class="flex justify-between gap-4">
                                <div class="flex min-w-0 gap-3">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-muted">
                                        @if($line->listing?->media->isNotEmpty())
                                            <img src="{{ $line->listing->media->first()->url() }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        @if($line->listing)
                                            <a href="{{ route('catalog.show', $line->listing->slug) }}"
                                               class="line-clamp-1 text-sm font-medium text-leaf-900 hover:text-leaf-700 dark:text-foreground dark:hover:text-primary">
                                                {{ $line->title() }}
                                            </a>
                                        @else
                                            <p class="line-clamp-1 text-sm font-medium text-leaf-900 dark:text-foreground">{{ $line->title() }}</p>
                                        @endif
                                        <p class="text-xs text-stone-500 dark:text-muted-foreground">
                                            {{ $line->qty }} × {{ \App\Support\Money::format((int) $line->unit_price) }}
                                            @if($line->lot_number)
                                                · {{ __('listing.lot') }} {{ $line->lot_number }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <p class="shrink-0 font-medium text-leaf-900 dark:text-foreground">{{ $line->formattedTotal() }}</p>
                            </div>

                            @if($order->status === \App\Enums\OrderStatus::Delivered)
                                <div class="mt-3 ms-[68px]">
                                    @if($line->review)
                                        <p class="flex items-center gap-1 text-xs font-medium text-leaf-700 dark:text-primary">
                                            <span class="flex items-center gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                         class="h-3 w-3 {{ $i <= $line->review->rating ? 'text-amber-400' : 'text-stone-200 dark:text-muted' }}">
                                                        <path d="m12 2.5 2.9 6.3 6.8.7-5.1 4.6 1.5 6.7L12 17.6l-6.1 3.2 1.5-6.7-5.1-4.6 6.8-.7L12 2.5Z"/>
                                                    </svg>
                                                @endfor
                                            </span>
                                            {{ __('order.review_submitted_short') }}
                                        </p>
                                    @else
                                        <div x-data="{ reviewing: false }">
                                            <button type="button" x-on:click="reviewing = ! reviewing"
                                                    class="text-xs font-semibold text-leaf-700 hover:underline dark:text-primary">
                                                {{ __('order.write_review') }}
                                            </button>

                                            <form method="post" action="{{ route('reviews.store', $line) }}" x-show="reviewing" x-cloak
                                                  class="mt-2 space-y-2 rounded-xl border border-stone-200 bg-stone-50 p-3 dark:border-border dark:bg-muted/40">
                                                @csrf
                                                <div x-data="{ rating: 5 }" class="flex items-center gap-1">
                                                    <template x-for="i in [1,2,3,4,5]" :key="i">
                                                        <button type="button" x-on:click="rating = i">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                                 class="h-5 w-5" :class="i <= rating ? 'text-amber-400' : 'text-stone-200 dark:text-muted'">
                                                                <path d="m12 2.5 2.9 6.3 6.8.7-5.1 4.6 1.5 6.7L12 17.6l-6.1 3.2 1.5-6.7-5.1-4.6 6.8-.7L12 2.5Z"/>
                                                            </svg>
                                                        </button>
                                                    </template>
                                                    <input type="hidden" name="rating" :value="rating">
                                                </div>
                                                <textarea name="body" rows="2" placeholder="{{ __('order.review_placeholder') }}"
                                                          class="w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground"></textarea>
                                                <button class="rounded-full bg-primary px-4 py-1.5 text-xs font-semibold text-primary-foreground hover:opacity-90">
                                                    {{ __('order.submit_review') }}
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($order->shipments->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card dark:border-border dark:bg-card">
                    <h2 class="border-b border-stone-200 px-4 py-2 font-semibold text-leaf-900 dark:border-border dark:text-foreground">{{ __('order.shipments_title') }}</h2>
                    <ul class="divide-y divide-stone-100 text-sm dark:divide-border">
                        @foreach($order->shipments as $shipment)
                            <li class="flex items-center justify-between gap-4 px-4 py-3">
                                <div>
                                    <p class="font-medium text-leaf-900 dark:text-foreground">
                                        {{ $shipment->carrier?->name ?? __('order.shipment_method.'.$shipment->method) }}
                                    </p>
                                    @if($shipment->tracking_ref)
                                        <p class="text-xs text-stone-500 dark:text-muted-foreground">{{ __('order.tracking_ref') }}: {{ $shipment->tracking_ref }}</p>
                                    @endif
                                </div>
                                <x-status-badge :status="$shipment->status" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($order->address)
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                    <h2 class="mb-2 font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.address') }}</h2>
                    <p class="text-sm text-stone-600 dark:text-muted-foreground">
                        {{ $order->address->contact_name }} · {{ $order->address->contact_phone }}<br>
                        {{ $order->address->oneLine() }}
                    </p>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card text-sm dark:border-border dark:bg-card">
                <div class="flex justify-between"><span class="text-stone-500 dark:text-muted-foreground">{{ __('cart.subtotal') }}</span><span class="text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format((int) $order->subtotal) }}</span></div>
                <div class="mt-1 flex justify-between"><span class="text-stone-500 dark:text-muted-foreground">{{ __('checkout.delivery') }}</span><span class="text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format((int) $order->delivery_total) }}</span></div>
                <div class="mt-1 flex justify-between text-xs text-stone-500 dark:text-muted-foreground"><span>{{ __('order.vat_included', ['rate' => config('marketplace.vat_rate')]) }}</span><span>{{ \App\Support\Money::format((int) $order->vat_total) }}</span></div>
                <div class="mt-3 flex justify-between border-t border-stone-100 pt-3 font-semibold text-leaf-900 dark:border-border dark:text-foreground"><span>{{ __('order.total') }}</span><span>{{ $order->formattedTotal() }}</span></div>
            </div>

            @if($order->payments->isNotEmpty())
                <div class="rounded-2xl border border-stone-200 bg-white p-4 text-sm shadow-card dark:border-border dark:bg-card">
                    <p class="font-medium text-leaf-900 dark:text-foreground">{{ $order->payment_method->label() }}</p>
                    <p class="text-xs text-stone-500 dark:text-muted-foreground">{{ $order->payments->first()->status->label() }}</p>
                </div>
            @endif

            @foreach($order->documents as $document)
                <div class="rounded-2xl border border-stone-200 bg-white p-4 text-sm shadow-card dark:border-border dark:bg-card">
                    <p class="font-medium text-leaf-900 dark:text-foreground">{{ __('order.document.'.$document->type) }}</p>
                    <p class="text-xs text-stone-500 dark:text-muted-foreground">{{ $document->number }}</p>
                </div>
            @endforeach
        </aside>
    </div>
@endsection

@extends('layouts.app')
@section('title', __('checkout.title'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.title') }}</h1>

    <form method="post" action="{{ route('checkout.store') }}" class="grid gap-8 lg:grid-cols-[1fr_360px]">
        @csrf

        <div class="space-y-6">
            <section class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.address') }}</h2>
                    <a href="{{ route('addresses.index') }}" class="text-xs font-semibold text-leaf-700 hover:underline dark:text-primary">
                        {{ __('account.manage_addresses') }}
                    </a>
                </div>

                @forelse($addresses as $address)
                    <label class="flex items-start gap-3 border-b border-stone-100 py-2.5 last:border-0 dark:border-border">
                        <input type="radio" name="address_id" value="{{ $address->id }}"
                               @checked($loop->first) required
                               class="mt-1 border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                        <span class="text-sm">
                            <span class="font-medium text-leaf-900 dark:text-foreground">
                                {{ $address->label ?: $address->contact_name }}
                                @if($address->is_default)
                                    <span class="ms-1 rounded-full bg-leaf-50 px-2 py-0.5 text-[10px] font-bold uppercase text-leaf-700 dark:bg-primary/15 dark:text-primary">
                                        {{ __('account.default_address') }}
                                    </span>
                                @endif
                            </span><br>
                            <span class="text-stone-500 dark:text-muted-foreground">{{ $address->oneLine() }} · {{ $address->contact_phone }}</span>
                        </span>
                    </label>
                @empty
                    <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                        {{ __('checkout.no_address') }}
                        <a href="{{ route('addresses.index') }}" class="font-semibold underline">{{ __('account.add_address') }}</a>
                    </div>
                @endforelse
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.payment') }}</h2>
                @foreach($methods as $method)
                    <label class="flex items-center gap-3 py-1.5 text-sm text-leaf-900 dark:text-foreground">
                        <input type="radio" name="payment_method" value="{{ $method->value }}"
                               @checked($loop->first) required
                               class="border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                        {{ $method->label() }}
                    </label>
                @endforeach
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <label class="block text-sm">
                    <span class="font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.note') }}</span>
                    <textarea name="note" rows="2"
                              class="mt-2 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground"></textarea>
                </label>
            </section>

            {{-- FR-103 — versioned, timestamped acceptance. Not a checkbox
                 for decoration: the version id is recorded against the user. --}}
            <label class="flex items-start gap-3 text-sm text-leaf-900 dark:text-foreground">
                <input type="checkbox" name="accept_terms" value="1" required
                       class="mt-1 rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                <span>
                    {{ __('checkout.accept_terms') }}
                    @if($buyerAgreement)
                        <span class="text-stone-500 dark:text-muted-foreground">({{ $buyerAgreement->translate('title') }} v{{ $buyerAgreement->version }})</span>
                    @endif
                </span>
            </label>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-card dark:border-border dark:bg-card">
                <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('checkout.summary') }}</h2>

                <div class="space-y-4">
                    @foreach($lines->where('available', true)->groupBy(fn ($l) => $l['listing']->seller()?->id ?? 'unknown') as $sellerLines)
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">
                                {{ $sellerLines->first()['listing']->sellerLabel() }}
                            </p>
                            <ul class="space-y-1.5 text-sm">
                                @foreach($sellerLines as $line)
                                    <li class="flex justify-between gap-3 text-leaf-900 dark:text-foreground">
                                        <span class="flex-1 truncate">{{ $line['listing']->name() }} × {{ $line['qty'] }}</span>
                                        <span class="shrink-0">{{ \App\Support\Money::format($line['line_total']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 space-y-1 border-t border-stone-100 pt-3 text-sm dark:border-border">
                    <div class="flex justify-between">
                        <span class="text-stone-500 dark:text-muted-foreground">{{ __('cart.subtotal') }}</span>
                        <span class="text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format($subtotal) }}</span>
                    </div>
                    @if($deliveryOptions->isNotEmpty())
                        <div class="flex justify-between">
                            <span class="text-stone-500 dark:text-muted-foreground">{{ __('checkout.delivery') }}</span>
                            <span class="text-leaf-900 dark:text-foreground">{{ \App\Support\Money::format($deliveryOptions->first()['price']) }}</span>
                        </div>
                        <p class="text-xs text-stone-400 dark:text-muted-foreground">
                            {{ trans_choice('checkout.delivery_days', $deliveryOptions->first()['days'], ['count' => $deliveryOptions->first()['days']]) }}
                        </p>
                    @endif
                    <div class="flex justify-between border-t border-stone-100 pt-2 font-semibold text-leaf-900 dark:border-border dark:text-foreground">
                        <span>{{ __('order.total') }}</span>
                        <span>{{ \App\Support\Money::format($subtotal + ($deliveryOptions->first()['price'] ?? 0)) }}</span>
                    </div>
                </div>
            </div>

            <button class="w-full rounded-full bg-gradient-brand px-6 py-3 font-semibold text-white shadow-card hover:opacity-90">
                {{ __('checkout.place_order') }}
            </button>
        </aside>
    </form>
@endsection

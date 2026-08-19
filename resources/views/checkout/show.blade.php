@extends('layouts.app')
@section('title', __('checkout.title'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('checkout.title') }}</h1>

    <form method="post" action="{{ route('checkout.store') }}" class="grid gap-8 lg:grid-cols-[1fr_360px]">
        @csrf

        <div class="space-y-6">
            <section class="rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ __('checkout.address') }}</h2>
                @forelse($addresses as $address)
                    <label class="flex items-start gap-3 border-b border-stone-100 py-2 last:border-0">
                        <input type="radio" name="address_id" value="{{ $address->id }}"
                               @checked($loop->first) required
                               class="mt-1 border-stone-300 text-leaf-600 focus:ring-leaf-500">
                        <span class="text-sm">
                            <span class="font-medium">{{ $address->contact_name }}</span><br>
                            <span class="text-stone-500">{{ $address->oneLine() }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-stone-500">{{ __('checkout.no_address') }}</p>
                @endforelse
            </section>

            <section class="rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ __('checkout.payment') }}</h2>
                @foreach($methods as $method)
                    <label class="flex items-center gap-3 py-1">
                        <input type="radio" name="payment_method" value="{{ $method->value }}"
                               @checked($loop->first) required
                               class="border-stone-300 text-leaf-600 focus:ring-leaf-500">
                        <span class="text-sm">{{ $method->label() }}</span>
                    </label>
                @endforeach
            </section>

            <section class="rounded-xl border border-stone-200 bg-white p-4">
                <label class="block text-sm">
                    <span class="font-semibold">{{ __('checkout.note') }}</span>
                    <textarea name="note" rows="2" class="mt-2 w-full rounded-lg border-stone-300 text-sm"></textarea>
                </label>
            </section>

            {{-- FR-103 — versioned, timestamped acceptance. Not a checkbox
                 for decoration: the version id is recorded against the user. --}}
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="accept_terms" value="1" required
                       class="mt-1 rounded border-stone-300 text-leaf-600 focus:ring-leaf-500">
                <span>
                    {{ __('checkout.accept_terms') }}
                    @if($buyerAgreement)
                        <span class="text-stone-500">({{ $buyerAgreement->translate('title') }} v{{ $buyerAgreement->version }})</span>
                    @endif
                </span>
            </label>
        </div>

        <aside class="space-y-4">
            <div class="rounded-xl border border-stone-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ __('checkout.summary') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach($lines->where('available', true) as $line)
                        <li class="flex justify-between gap-3">
                            <span class="flex-1">{{ $line['listing']->name() }} × {{ $line['qty'] }}</span>
                            <span>{{ \App\Support\Money::format($line['line_total']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 border-t border-stone-100 pt-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-stone-500">{{ __('cart.subtotal') }}</span>
                        <span>{{ \App\Support\Money::format($subtotal) }}</span>
                    </div>
                    @if($deliveryOptions->isNotEmpty())
                        <div class="mt-1 flex justify-between">
                            <span class="text-stone-500">{{ __('checkout.delivery') }}</span>
                            <span>{{ \App\Support\Money::format($deliveryOptions->first()['price']) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <button class="w-full rounded-lg bg-leaf-600 px-6 py-3 font-medium text-white hover:bg-leaf-700">
                {{ __('checkout.place_order') }}
            </button>
        </aside>
    </form>
@endsection

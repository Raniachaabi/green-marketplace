@extends('layouts.app')
@section('title', __('common.cart'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('common.cart') }}</h1>

    @if($lines->isEmpty())
        <p class="text-sm text-stone-500">{{ __('cart.empty') }}</p>
    @else
        <div class="space-y-3">
            @foreach($lines as $line)
                <div class="flex items-center gap-4 rounded-xl border border-stone-200 bg-white p-4
                            {{ $line['available'] ? '' : 'opacity-60' }}">
                    <div class="flex-1">
                        <p class="font-medium">{{ $line['listing']->name() }}</p>
                        <p class="text-xs text-stone-500">{{ $line['listing']->sellerLabel() }}</p>
                        @unless($line['available'])
                            <p class="mt-1 text-xs text-red-600">{{ $line['reason'] }}</p>
                        @endunless
                    </div>

                    <form method="post" action="{{ route('cart.update', $line['listing']->id) }}" class="flex items-center gap-2">
                        @csrf @method('patch')
                        <input type="number" name="qty" value="{{ $line['qty'] }}" step="0.001" min="0"
                               class="w-24 rounded-lg border-stone-300 text-sm">
                        <button class="text-xs text-leaf-700 underline">{{ __('cart.update_action') }}</button>
                    </form>

                    <p class="w-28 text-end font-medium">{{ \App\Support\Money::format($line['line_total']) }}</p>

                    <form method="post" action="{{ route('cart.remove', $line['listing']->id) }}">
                        @csrf @method('delete')
                        <button class="text-xs text-red-600 underline">{{ __('cart.remove_action') }}</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between rounded-xl bg-white p-4">
            <span class="text-sm text-stone-500">{{ __('cart.subtotal') }}</span>
            <span class="text-lg font-semibold">{{ \App\Support\Money::format($subtotal) }}</span>
        </div>

        <div class="mt-4 text-end">
            <a href="{{ route('checkout.show') }}"
               class="inline-block rounded-lg bg-leaf-600 px-6 py-2 font-medium text-white hover:bg-leaf-700">
                {{ __('cart.checkout') }}
            </a>
        </div>
    @endif
@endsection

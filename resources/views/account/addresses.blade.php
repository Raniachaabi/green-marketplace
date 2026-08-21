@extends('layouts.app')
@section('title', __('account.addresses_title'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-6" x-data="{ adding: {{ $addresses->isEmpty() ? 'true' : 'false' }}, editing: null }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('account.addresses_title') }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">{{ __('account.addresses_intro') }}</p>
            </div>
            <button type="button" x-on:click="adding = ! adding"
                    class="rounded-full bg-gradient-brand px-5 py-2.5 text-sm font-semibold text-white shadow-card hover:opacity-90">
                {{ __('account.add_address') }}
            </button>
        </div>

        <form method="post" action="{{ route('addresses.store') }}" x-show="adding" x-cloak
              class="space-y-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
            @csrf
            <h2 class="font-semibold text-leaf-900 dark:text-foreground">{{ __('account.add_address') }}</h2>
            @include('account._address-fields', ['address' => null])
            <div class="flex gap-2">
                <button class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                    {{ __('account.save_address') }}
                </button>
                <button type="button" x-on:click="adding = false"
                        class="rounded-full px-5 py-2 text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:text-muted-foreground dark:hover:bg-accent">
                    {{ __('account.cancel') }}
                </button>
            </div>
        </form>

        @forelse($addresses as $address)
            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
                <div class="flex items-start justify-between gap-4" x-show="editing !== '{{ $address->id }}'">
                    <div class="text-sm">
                        <p class="flex items-center gap-2 font-semibold text-leaf-900 dark:text-foreground">
                            {{ $address->label ?: $address->contact_name }}
                            @if($address->is_default)
                                <span class="rounded-full bg-leaf-50 px-2 py-0.5 text-[10px] font-bold uppercase text-leaf-700 dark:bg-primary/15 dark:text-primary">
                                    {{ __('account.default_address') }}
                                </span>
                            @endif
                        </p>
                        <p class="mt-1 text-stone-600 dark:text-muted-foreground">{{ $address->contact_name }} · {{ $address->contact_phone }}</p>
                        <p class="text-stone-500 dark:text-muted-foreground">{{ $address->oneLine() }}</p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2 text-xs font-semibold">
                        <div class="flex gap-3">
                            <button type="button" x-on:click="editing = '{{ $address->id }}'" class="text-leaf-700 hover:underline dark:text-primary">
                                {{ __('account.edit') }}
                            </button>
                            <form method="post" action="{{ route('addresses.destroy', $address) }}"
                                  onsubmit="return confirm('{{ __('account.confirm_delete_address') }}')">
                                @csrf @method('delete')
                                <button class="text-red-600 hover:underline dark:text-destructive">{{ __('account.delete') }}</button>
                            </form>
                        </div>
                        @unless($address->is_default)
                            <form method="post" action="{{ route('addresses.default', $address) }}">
                                @csrf
                                <button class="text-stone-500 hover:underline dark:text-muted-foreground">{{ __('account.make_default') }}</button>
                            </form>
                        @endunless
                    </div>
                </div>

                <form method="post" action="{{ route('addresses.update', $address) }}"
                      x-show="editing === '{{ $address->id }}'" x-cloak class="space-y-4">
                    @csrf @method('put')
                    @include('account._address-fields', ['address' => $address])
                    <div class="flex gap-2">
                        <button class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                            {{ __('account.save_address') }}
                        </button>
                        <button type="button" x-on:click="editing = null"
                                class="rounded-full px-5 py-2 text-sm font-semibold text-stone-500 hover:bg-stone-100 dark:text-muted-foreground dark:hover:bg-accent">
                            {{ __('account.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('account.no_addresses') }}</p>
            </div>
        @endforelse
    </div>
@endsection

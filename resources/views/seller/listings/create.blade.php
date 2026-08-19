@extends('layouts.app')
@section('title', __('seller.new_listing'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <h1 class="text-2xl font-semibold">{{ __('seller.new_listing') }}</h1>

        @unless($category)
            {{-- Categories the seller cannot sell in are shown, but disabled
                 with the reason. Hiding them entirely leaves people wondering
                 where their category went; showing them with "you need X"
                 turns a dead end into an onboarding step. --}}
            <div class="space-y-2">
                @foreach($categories as $entry)
                    <div class="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $entry['category']->name() }}</p>
                            @unless($entry['eligible'])
                                <p class="text-xs text-amber-700">
                                    {{ __('seller.requires') }}: {{ $entry['missing']->implode(', ') }}
                                </p>
                            @endunless
                        </div>
                        @if($entry['eligible'])
                            <a href="{{ route('seller.listings.create', ['category' => $entry['category']->id]) }}"
                               class="text-xs text-leaf-700 underline">{{ __('seller.choose') }}</a>
                        @else
                            <a href="{{ route('seller.onboarding') }}"
                               class="text-xs text-stone-500 underline">{{ __('seller.provide_documents') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <form method="post" action="{{ route('seller.listings.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="category_id" value="{{ $category->id }}">

                <section class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                    <h2 class="font-semibold">{{ $category->name() }}</h2>

                    @foreach(config('marketplace.locales') as $code => $meta)
                        <label class="block text-sm">
                            <span class="text-stone-500">{{ __('seller.title') }} ({{ $meta['name'] }})</span>
                            <input name="title[{{ $code }}]" class="mt-1 w-full rounded-lg border-stone-300 text-sm"
                                   dir="{{ $meta['dir'] }}">
                        </label>
                    @endforeach

                    <label class="block text-sm">
                        <span class="text-stone-500">{{ __('seller.description') }}</span>
                        <textarea name="description[{{ app()->getLocale() }}]" rows="4"
                                  class="mt-1 w-full rounded-lg border-stone-300 text-sm"></textarea>
                    </label>
                </section>

                <section class="grid gap-3 rounded-xl border border-stone-200 bg-white p-4 sm:grid-cols-2">
                    <label class="text-sm">
                        <span class="text-stone-500">{{ __('seller.price') }} (TND)</span>
                        <input name="price" type="number" step="0.001" required
                               class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                        @if($priceCap)
                            <span class="mt-1 block text-xs text-amber-700">
                                {{ __('seller.max_price', ['ceiling' => $priceCap->formattedCeiling()]) }}
                            </span>
                        @endif
                    </label>

                    <label class="text-sm">
                        <span class="text-stone-500">{{ __('seller.unit') }}</span>
                        <input name="unit" value="{{ $priceCap->unit ?? 'piece' }}" required
                               class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                    </label>

                    <label class="text-sm">
                        <span class="text-stone-500">{{ __('seller.stock') }}</span>
                        <input name="stock" type="number" min="0" value="0"
                               class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                    </label>

                    <label class="text-sm">
                        <span class="text-stone-500">{{ __('seller.availability') }}</span>
                        <select name="availability_model" class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                            @foreach(\App\Enums\AvailabilityModel::cases() as $model)
                                <option value="{{ $model->value }}">{{ $model->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-sm">
                        <span class="text-stone-500">{{ __('seller.season_start') }}</span>
                        <input name="season_start" type="date" class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                    </label>

                    <label class="text-sm">
                        <span class="text-stone-500">{{ __('seller.season_end') }}</span>
                        <input name="season_end" type="date" class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                    </label>

                    @if($rule?->requires_lot_number)
                        <label class="text-sm sm:col-span-2">
                            <span class="text-stone-500">{{ __('seller.lot_number') }} *</span>
                            <input name="lot_number" required class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                            <span class="mt-1 block text-xs text-stone-500">{{ __('seller.lot_number_help') }}</span>
                        </label>
                    @endif
                </section>

                {{-- FR-012 — this whole block is generated from the category.
                     No PHP here knows what wheat seed or rose water is. --}}
                @if($fields->isNotEmpty())
                    <section class="grid gap-3 rounded-xl border border-stone-200 bg-white p-4 sm:grid-cols-2">
                        <h2 class="font-semibold sm:col-span-2">{{ __('seller.category_fields') }}</h2>

                        @foreach($fields as $field)
                            <label class="text-sm">
                                <span class="text-stone-500">
                                    {{ $field->name() }}@if($field->required) *@endif
                                    @if($field->unit) <span class="text-stone-400">({{ $field->unit }})</span>@endif
                                </span>

                                @switch($field->data_type)
                                    @case('boolean')
                                        <select name="attributes[{{ $field->key }}]"
                                                class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                                            <option value="0">{{ __('common.no') }}</option>
                                            <option value="1">{{ __('common.yes') }}</option>
                                        </select>
                                        @break

                                    @case('select')
                                        <select name="attributes[{{ $field->key }}]" @required($field->required)
                                                class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                                            <option value="">—</option>
                                            @foreach(($field->options ?? []) as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('text')
                                        <textarea name="attributes[{{ $field->key }}]" rows="3" @required($field->required)
                                                  class="mt-1 w-full rounded-lg border-stone-300 text-sm"></textarea>
                                        @break

                                    @case('number')
                                    @case('integer')
                                        <input type="number" step="{{ $field->data_type === 'integer' ? '1' : 'any' }}"
                                               name="attributes[{{ $field->key }}]" @required($field->required)
                                               class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                                        @break

                                    @case('date')
                                        <input type="date" name="attributes[{{ $field->key }}]" @required($field->required)
                                               class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                                        @break

                                    @default
                                        <input name="attributes[{{ $field->key }}]" @required($field->required)
                                               class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                                @endswitch
                            </label>
                        @endforeach
                    </section>
                @endif

                <section class="rounded-xl border border-stone-200 bg-white p-4">
                    <h2 class="mb-3 font-semibold">{{ __('seller.green_attributes') }}</h2>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach($greenAttributes as $attribute)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="green[]" value="{{ $attribute->code }}"
                                       class="rounded border-stone-300 text-leaf-600 focus:ring-leaf-500">
                                <span>{{ $attribute->name() }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <button class="rounded-lg bg-leaf-600 px-6 py-2 font-medium text-white hover:bg-leaf-700">
                    {{ __('seller.save_draft') }}
                </button>
            </form>
        @endunless
    </div>
@endsection

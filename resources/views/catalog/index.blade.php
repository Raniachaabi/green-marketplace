@extends('layouts.app')
@section('title', __('common.catalogue'))

@section('content')
    <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
        <aside>
            <form method="get" class="sticky top-24 space-y-5 rounded-2xl border border-stone-200 bg-white p-5 shadow-card text-sm">
                <input type="hidden" name="q" value="{{ request('q') }}">

                <div>
                    <h3 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-stone-400">
                        {{ __('catalog.category') }}
                    </h3>
                    <select name="category" class="w-full rounded-lg border-stone-300 text-sm focus:border-leaf-500 focus:ring-leaf-500">
                        <option value="">{{ __('catalog.all') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                                {{ $category->name() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t border-stone-100 pt-5">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ __('catalog.governorate') }}</h3>
                    <select name="governorate" class="w-full rounded-lg border-stone-300 text-sm focus:border-leaf-500 focus:ring-leaf-500">
                        <option value="">{{ __('catalog.anywhere') }}</option>
                        @foreach($governorates as $governorate)
                            <option value="{{ $governorate }}" @selected(request('governorate') === $governorate)>
                                {{ __('governorate.'.$governorate) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t border-stone-100 pt-5">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ __('catalog.values') }}</h3>
                    <div class="space-y-2">
                        @foreach($greenAttributes as $attribute)
                            <label class="flex items-center gap-2 rounded-lg px-1 py-0.5 hover:bg-leaf-50">
                                <input type="checkbox" name="green[]" value="{{ $attribute->code }}"
                                       @checked(in_array($attribute->code, (array) request('green', [])))
                                       class="rounded border-stone-300 text-leaf-600 focus:ring-leaf-500">
                                <span>{{ $attribute->name() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-stone-100 pt-5">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ __('catalog.sort') }}</h3>
                    <select name="sort" class="w-full rounded-lg border-stone-300 text-sm focus:border-leaf-500 focus:ring-leaf-500">
                        <option value="newest" @selected(request('sort') === 'newest')>{{ __('catalog.newest') }}</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('catalog.price_asc') }}</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('catalog.price_desc') }}</option>
                    </select>
                </div>

                <button class="w-full rounded-full bg-leaf-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-leaf-700">
                    {{ __('catalog.apply') }}
                </button>
            </form>
        </aside>

        <div>
            <p class="mb-5 text-sm font-medium text-stone-500">
                {{ trans_choice('catalog.results', $listings->total(), ['count' => $listings->total()]) }}
            </p>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white py-12 text-center text-sm text-stone-500">
                        {{ __('catalog.empty') }}
                    </p>
                @endforelse
            </div>

            <div class="mt-8">{{ $listings->links() }}</div>
        </div>
    </div>
@endsection

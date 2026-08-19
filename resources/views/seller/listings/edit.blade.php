@extends('layouts.app')
@section('title', $listing->name())

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">{{ $listing->name() }}</h1>
                <p class="mt-1 text-sm text-stone-500">
                    {{ $category?->name() }} · {{ $listing->formattedPrice() }}
                </p>
            </div>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs">{{ $listing->status->label() }}</span>
        </div>

        {{--
            The seller sees the real gate output, not a generic "not published"
            message. Every line here is something they can act on, and the
            distinction between "expired" and "missing" tells them whether to
            renew or to apply.
        --}}
        @if($violations->isNotEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h2 class="text-sm font-semibold text-amber-900">{{ __('seller.blocked_title') }}</h2>
                <ul class="mt-2 space-y-1 text-sm text-amber-900">
                    @foreach($violations as $violation)
                        <li class="flex gap-2">
                            <span aria-hidden="true">•</span>
                            <span>
                                {{ $violation->message }}
                                @unless($violation->fixableBySeller)
                                    <span class="text-xs text-amber-700">({{ __('seller.not_fixable') }})</span>
                                @endunless
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="rounded-xl border border-leaf-200 bg-leaf-50 p-4 text-sm text-leaf-800">
                {{ __('seller.ready_to_publish') }}
            </div>
        @endif

        <form method="post" action="{{ route('seller.listings.publish', $listing) }}">
            @csrf
            <button class="rounded-lg bg-leaf-600 px-6 py-2 font-medium text-white hover:bg-leaf-700
                           disabled:cursor-not-allowed disabled:bg-stone-300"
                    @disabled($violations->isNotEmpty())>
                {{ __('seller.publish') }}
            </button>
        </form>

        @if($fields->isNotEmpty())
            <section class="rounded-xl border border-stone-200 bg-white">
                <h2 class="border-b border-stone-200 px-4 py-2 text-sm font-semibold">
                    {{ __('seller.category_fields') }}
                </h2>
                <dl class="divide-y divide-stone-100 text-sm">
                    @foreach($fields as $field)
                        <div class="flex justify-between gap-4 px-4 py-2">
                            <dt class="text-stone-500">
                                {{ $field->name() }}@if($field->required) *@endif
                            </dt>
                            <dd class="text-end">
                                {{ $listing->attr($field->key) ?: '—' }} {{ $field->unit }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        <a href="{{ route('seller.listings.index') }}" class="inline-block text-sm text-leaf-700 underline">
            {{ __('common.back') }}
        </a>
    </div>
@endsection

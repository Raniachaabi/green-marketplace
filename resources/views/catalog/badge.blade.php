@extends('layouts.app')
@section('title', $badge->name())

@section('content')
    {{--
        FR-024 — badge disclosure.

        This page exists because "Bio certifié" on a listing is a
        representation a buyer relies on. Saying precisely what was checked,
        by whom, and under which law is both honest and the thing that keeps
        the badge defensible.
    --}}
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <span class="rounded-full bg-leaf-50 px-3 py-1 text-sm text-leaf-700">{{ $badge->name() }}</span>
            <h1 class="mt-4 text-2xl font-semibold">{{ __('badge.what_this_means') }}</h1>
        </div>

        @if($badge->translate('description'))
            <p class="text-sm leading-relaxed text-stone-700">{{ $badge->translate('description') }}</p>
        @endif

        <div class="rounded-xl border border-stone-200 bg-white">
            <h2 class="border-b border-stone-200 px-4 py-2 text-sm font-semibold">{{ __('badge.checked') }}</h2>
            <ul class="divide-y divide-stone-100 text-sm">
                @foreach($badge->credentialTypes as $type)
                    <li class="space-y-1 px-4 py-3">
                        <p class="font-medium">{{ $type->name() }}</p>
                        @if($type->issuing_body)
                            <p class="text-xs text-stone-500">{{ __('badge.issued_by') }}: {{ $type->issuing_body }}</p>
                        @endif
                        @if($type->legal_reference)
                            <p class="text-xs text-stone-500">{{ __('badge.legal_basis') }}: {{ $type->legal_reference }}</p>
                        @endif
                        @if($type->requires_expiry)
                            <p class="text-xs text-leaf-700">{{ __('badge.expiry_enforced') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="rounded-xl bg-stone-100 p-4 text-xs leading-relaxed text-stone-600">
            {{ __('badge.disclaimer') }}
        </p>
    </div>
@endsection

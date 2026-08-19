@extends('layouts.app')
@section('title', __('seller.onboarding_title'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('seller.onboarding_title') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('seller.onboarding_intro') }}</p>
        </div>

        @if($badges->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($badges as $badge)
                    <span class="rounded-full bg-leaf-50 px-3 py-1 text-xs text-leaf-700">{{ $badge->name() }}</span>
                @endforeach
            </div>
        @endif

        {{-- FR-020 — the seller picks categories first, and only then is
             asked for documents. Asking everyone for everything is how you
             lose the home producer who only wanted to sell soap. --}}
        <form method="post" action="{{ route('seller.requirements') }}" class="space-y-4">
            @csrf
            <h2 class="font-semibold">{{ __('seller.what_do_you_sell') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($categories as $category)
                    <label class="flex items-center gap-2 rounded-lg border border-stone-200 bg-white p-3 text-sm">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               class="rounded border-stone-300 text-leaf-600 focus:ring-leaf-500">
                        <span>{{ $category->name() }}</span>
                    </label>
                @endforeach
            </div>
            <button class="rounded-lg bg-leaf-600 px-6 py-2 font-medium text-white hover:bg-leaf-700">
                {{ __('seller.show_requirements') }}
            </button>
        </form>

        <section>
            <h2 class="mb-3 font-semibold">{{ __('seller.your_documents') }}</h2>
            <div class="space-y-2">
                @forelse($credentials as $credential)
                    <div class="flex items-center justify-between rounded-lg border border-stone-200 bg-white p-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $credential->credentialType?->name() }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $credential->number }}
                                @if($credential->expires_at)
                                    · {{ __('seller.valid_until') }} {{ $credential->expires_at->format('d/m/Y') }}
                                @endif
                            </p>
                            @if($credential->rejection_reason)
                                <p class="mt-1 text-xs text-red-600">{{ $credential->rejection_reason }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-stone-100 px-3 py-1 text-xs">{{ $credential->status->label() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">{{ __('seller.no_documents') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

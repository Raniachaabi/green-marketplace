@extends('layouts.app')
@section('title', __('seller.onboarding_title'))

@section('content')
    <x-seller-nav active="onboarding" />

    <div class="mx-auto max-w-3xl space-y-8">
        <div>
            <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.onboarding_title') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-muted-foreground">{{ __('seller.onboarding_intro') }}</p>
        </div>

        @if($badges->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($badges as $badge)
                    <span class="rounded-full bg-leaf-50 px-3 py-1 text-xs text-leaf-700 dark:bg-primary/15 dark:text-primary">{{ $badge->name() }}</span>
                @endforeach
            </div>
        @endif

        {{-- FR-020 — the seller picks categories first, and only then is
             asked for documents. Asking everyone for everything is how you
             lose the home producer who only wanted to sell soap. --}}
        <form method="post" action="{{ route('seller.requirements') }}" class="space-y-4">
            @csrf
            <h2 class="font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.what_do_you_sell') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($categories as $category)
                    <label class="flex items-center gap-2 rounded-lg border border-stone-200 bg-white p-3 text-sm dark:border-border dark:bg-card dark:text-foreground">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               class="rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                        <span>{{ $category->name() }}</span>
                    </label>
                @endforeach
            </div>
            <button class="rounded-full bg-gradient-brand px-6 py-2 font-medium text-white shadow-sm hover:opacity-90">
                {{ __('seller.show_requirements') }}
            </button>
        </form>

        <section>
            <h2 class="mb-3 font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.your_documents') }}</h2>
            <div class="space-y-2">
                @forelse($credentials as $credential)
                    <div class="flex items-center justify-between rounded-lg border border-stone-200 bg-white p-3 text-sm dark:border-border dark:bg-card">
                        <div>
                            <p class="font-medium text-leaf-900 dark:text-foreground">{{ $credential->credentialType?->name() }}</p>
                            <p class="text-xs text-stone-500 dark:text-muted-foreground">
                                {{ $credential->number }}
                                @if($credential->expires_at)
                                    · {{ __('seller.valid_until') }} {{ $credential->expires_at->format('d/m/Y') }}
                                @endif
                            </p>
                            @if($credential->rejection_reason)
                                <p class="mt-1 text-xs text-red-600 dark:text-destructive">{{ $credential->rejection_reason }}</p>
                            @endif
                        </div>
                        <x-status-badge :status="$credential->status" />
                    </div>
                @empty
                    <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.no_documents') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

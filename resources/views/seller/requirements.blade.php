@extends('layouts.app')
@section('title', __('seller.requirements_title'))

@section('content')
    <x-seller-nav active="onboarding" />

    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.requirements_title') }}</h1>

        @if($missing->isEmpty())
            <p class="rounded-xl bg-leaf-50 p-4 text-sm text-leaf-800 dark:bg-primary/10 dark:text-primary">{{ __('seller.all_requirements_met') }}</p>
        @else
            <p class="text-sm text-stone-600 dark:text-muted-foreground">{{ __('seller.requirements_intro') }}</p>
        @endif

        @foreach($types as $type)
            <div class="rounded-xl border border-stone-200 bg-white p-4 dark:border-border dark:bg-card">
                <div class="mb-3">
                    <p class="font-medium text-leaf-900 dark:text-foreground">{{ $type->name() }}</p>
                    @if($type->translate('description'))
                        <p class="mt-1 text-xs text-stone-600 dark:text-muted-foreground">{{ $type->translate('description') }}</p>
                    @endif
                    @if($type->legal_reference)
                        <p class="mt-1 text-xs text-stone-500 dark:text-muted-foreground/70">{{ $type->legal_reference }}</p>
                    @endif
                </div>

                @if($missing->contains($type->code))
                    <form method="post" action="{{ route('seller.credentials.store') }}"
                          enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="credential_type_code" value="{{ $type->code }}">

                        <label class="text-sm">
                            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.document_number') }}</span>
                            <input name="number" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground"
                                   @required($type->requires_number)>
                        </label>

                        <label class="text-sm">
                            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.issuer') }}</span>
                            <input name="issuer" value="{{ $type->issuing_body }}"
                                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                        </label>

                        @if($type->requires_expiry)
                            <label class="text-sm">
                                <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.expires_at') }}</span>
                                <input type="date" name="expires_at" required
                                       class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
                            </label>
                        @endif

                        @if($type->requires_document)
                            <label class="text-sm">
                                <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.upload') }}</span>
                                <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png"
                                       class="mt-1 w-full text-sm dark:text-foreground">
                            </label>
                        @endif

                        <div class="sm:col-span-2">
                            <button class="rounded-full bg-gradient-brand px-5 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90">
                                {{ __('seller.submit_document') }}
                            </button>
                        </div>
                    </form>
                @else
                    <p class="text-xs text-leaf-700 dark:text-primary">{{ __('seller.already_provided') }}</p>
                @endif
            </div>
        @endforeach

        <a href="{{ route('seller.onboarding') }}" class="inline-block text-sm text-leaf-700 underline dark:text-primary">
            {{ __('common.back') }}
        </a>
    </div>
@endsection

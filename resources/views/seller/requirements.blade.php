@extends('layouts.app')
@section('title', __('seller.requirements_title'))

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="text-2xl font-semibold">{{ __('seller.requirements_title') }}</h1>

        @if($missing->isEmpty())
            <p class="rounded-xl bg-leaf-50 p-4 text-sm text-leaf-800">{{ __('seller.all_requirements_met') }}</p>
        @else
            <p class="text-sm text-stone-600">{{ __('seller.requirements_intro') }}</p>
        @endif

        @foreach($types as $type)
            <div class="rounded-xl border border-stone-200 bg-white p-4">
                <div class="mb-3">
                    <p class="font-medium">{{ $type->name() }}</p>
                    @if($type->translate('description'))
                        <p class="mt-1 text-xs text-stone-600">{{ $type->translate('description') }}</p>
                    @endif
                    @if($type->legal_reference)
                        <p class="mt-1 text-xs text-stone-500">{{ $type->legal_reference }}</p>
                    @endif
                </div>

                @if($missing->contains($type->code))
                    <form method="post" action="{{ route('seller.credentials.store') }}"
                          enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="credential_type_code" value="{{ $type->code }}">

                        <label class="text-sm">
                            <span class="text-stone-500">{{ __('seller.document_number') }}</span>
                            <input name="number" class="mt-1 w-full rounded-lg border-stone-300 text-sm"
                                   @required($type->requires_number)>
                        </label>

                        <label class="text-sm">
                            <span class="text-stone-500">{{ __('seller.issuer') }}</span>
                            <input name="issuer" value="{{ $type->issuing_body }}"
                                   class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                        </label>

                        @if($type->requires_expiry)
                            <label class="text-sm">
                                <span class="text-stone-500">{{ __('seller.expires_at') }}</span>
                                <input type="date" name="expires_at" required
                                       class="mt-1 w-full rounded-lg border-stone-300 text-sm">
                            </label>
                        @endif

                        @if($type->requires_document)
                            <label class="text-sm">
                                <span class="text-stone-500">{{ __('seller.upload') }}</span>
                                <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png"
                                       class="mt-1 w-full text-sm">
                            </label>
                        @endif

                        <div class="sm:col-span-2">
                            <button class="rounded-lg bg-leaf-600 px-5 py-2 text-sm font-medium text-white hover:bg-leaf-700">
                                {{ __('seller.submit_document') }}
                            </button>
                        </div>
                    </form>
                @else
                    <p class="text-xs text-leaf-700">{{ __('seller.already_provided') }}</p>
                @endif
            </div>
        @endforeach

        <a href="{{ route('seller.onboarding') }}" class="inline-block text-sm text-leaf-700 underline">
            {{ __('common.back') }}
        </a>
    </div>
@endsection

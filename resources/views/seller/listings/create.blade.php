@extends('layouts.app')
@section('title', __('seller.new_listing'))

@section('content')
    <x-seller-nav active="create" />

    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.new_listing') }}</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.new_listing_intro') }}</p>
        </div>

        @unless($category)
            {{-- Categories the seller cannot sell in are shown, but disabled
                 with the reason. Hiding them entirely leaves people wondering
                 where their category went; showing them with "you need X"
                 turns a dead end into an onboarding step. --}}
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($categories as $entry)
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-sm shadow-card dark:border-border dark:bg-card">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-leaf-900 dark:text-foreground">{{ $entry['category']->name() }}</p>
                            @unless($entry['eligible'])
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                    {{ __('seller.requires') }}: {{ $entry['missing']->implode(', ') }}
                                </p>
                            @else
                                <p class="mt-1 text-xs text-leaf-700 dark:text-primary">{{ __('seller.ready_to_list') }}</p>
                            @endunless
                        </div>
                        @if($entry['eligible'])
                            <a href="{{ route('seller.listings.create', ['category' => $entry['category']->id]) }}"
                               class="shrink-0 rounded-full bg-gradient-brand px-4 py-2 text-xs font-semibold text-white shadow-sm hover:opacity-90">
                                {{ __('seller.choose') }}
                            </a>
                        @else
                            <a href="{{ route('seller.onboarding') }}"
                               class="shrink-0 rounded-full border border-stone-200 px-3 py-2 text-xs font-medium text-stone-500 hover:border-leaf-300 hover:text-leaf-700 dark:border-border dark:text-muted-foreground dark:hover:border-primary/50 dark:hover:text-primary">
                                {{ __('seller.provide_documents') }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <form method="post" action="{{ route('seller.listings.store') }}" class="space-y-6 pb-24">
                @csrf
                <input type="hidden" name="category_id" value="{{ $category->id }}">

                <div class="flex items-center justify-between rounded-2xl border border-leaf-200 bg-leaf-50 px-4 py-3 text-sm dark:border-primary/30 dark:bg-primary/10">
                    <span class="font-medium text-leaf-800 dark:text-primary">{{ $category->name() }}</span>
                    <a href="{{ route('seller.listings.create') }}" class="text-xs font-medium text-leaf-700 underline dark:text-primary">
                        {{ __('seller.change_category') }}
                    </a>
                </div>

                @include('seller.listings._form-fields')

                <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-4 text-sm text-stone-600 dark:border-border dark:bg-muted/40 dark:text-muted-foreground">
                    {{ __('seller.photos_after_save') }}
                </div>

                {{-- Sticky so the primary action is always reachable, even on a long
                     category-field form. --}}
                <div class="fixed inset-x-0 bottom-0 z-20 border-t border-stone-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-border dark:bg-background/95">
                    <div class="mx-auto flex max-w-3xl items-center justify-between">
                        <p class="hidden text-xs text-stone-500 sm:block dark:text-muted-foreground">{{ __('seller.save_draft_help') }}</p>
                        <button class="ms-auto rounded-full bg-gradient-brand px-6 py-2.5 font-semibold text-white shadow-card hover:opacity-90">
                            {{ __('seller.save_draft') }}
                        </button>
                    </div>
                </div>
            </form>
        @endunless
    </div>
@endsection

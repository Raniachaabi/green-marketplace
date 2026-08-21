@extends('layouts.app')
@section('title', $listing->name())

@section('content')
    <x-seller-nav active="listings" />

    <div class="mx-auto max-w-3xl space-y-6 pb-10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ $listing->name() }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">
                    {{ $category?->name() }} · {{ $listing->formattedPrice() }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <x-status-badge :status="$listing->status" />

                @if($listing->status === \App\Enums\ListingStatus::Active)
                    <a href="{{ route('catalog.show', $listing->slug) }}" target="_blank" rel="noopener"
                       class="rounded-full border border-stone-200 px-3 py-1.5 text-xs font-medium text-stone-600 hover:border-leaf-300 hover:text-leaf-700 dark:border-border dark:text-muted-foreground dark:hover:border-primary/50 dark:hover:text-primary">
                        {{ __('seller.view_live') }}
                    </a>
                @endif
            </div>
        </div>

        @if($listing->status_reason)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                {{ $listing->status_reason === 'edited_after_publication' ? __('seller.status_reason_edited') : $listing->status_reason }}
            </div>
        @endif

        {{--
            The seller sees the real gate output, not a generic "not published"
            message. Every line here is something they can act on, and the
            distinction between "expired" and "missing" tells them whether to
            renew or to apply.
        --}}
        <section class="rounded-2xl border p-4
                        {{ $violations->isNotEmpty()
                            ? 'border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10'
                            : 'border-leaf-200 bg-leaf-50 dark:border-primary/30 dark:bg-primary/10' }}">
            @if($violations->isNotEmpty())
                <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-300">{{ __('seller.blocked_title') }}</h2>
                <ul class="mt-2 space-y-1 text-sm text-amber-900 dark:text-amber-300">
                    @foreach($violations as $violation)
                        <li class="flex gap-2">
                            <span aria-hidden="true">•</span>
                            <span>
                                {{ $violation->message }}
                                @unless($violation->fixableBySeller)
                                    <span class="text-xs text-amber-700 dark:text-amber-400">({{ __('seller.not_fixable') }})</span>
                                @endunless
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-leaf-800 dark:text-primary">{{ __('seller.ready_to_publish') }}</p>
            @endif

            <div class="mt-3 flex flex-wrap items-center gap-3">
                @if($listing->status !== \App\Enums\ListingStatus::Active)
                    <form method="post" action="{{ route('seller.listings.publish', $listing) }}">
                        @csrf
                        <button class="rounded-full bg-gradient-brand px-5 py-2 text-sm font-semibold text-white shadow-sm
                                       disabled:cursor-not-allowed disabled:opacity-50"
                                @disabled($violations->isNotEmpty())>
                            {{ __('seller.publish') }}
                        </button>
                    </form>
                @else
                    <form method="post" action="{{ route('seller.listings.unpublish', $listing) }}"
                          onsubmit="return confirm('{{ __('seller.confirm_unpublish') }}')">
                        @csrf
                        <button class="rounded-full border border-stone-300 px-5 py-2 text-sm font-semibold text-stone-700 hover:border-red-300 hover:text-red-700 dark:border-border dark:text-foreground dark:hover:border-destructive/50 dark:hover:text-destructive">
                            {{ __('seller.unpublish') }}
                        </button>
                    </form>
                @endif

                @if(in_array($listing->status, [\App\Enums\ListingStatus::Draft, \App\Enums\ListingStatus::Rejected], true))
                    <form method="post" action="{{ route('seller.listings.destroy', $listing) }}"
                          onsubmit="return confirm('{{ __('seller.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-full px-5 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-destructive dark:hover:bg-destructive/10">
                            {{ __('seller.delete_listing') }}
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Photos — the gate's #1 blocker in practice, so it gets top billing
             right under the readiness panel rather than buried in the form. --}}
        <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
            <h2 class="mb-1 font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.section_photos') }}</h2>
            <p class="mb-4 text-xs text-stone-500 dark:text-muted-foreground">{{ __('seller.photos_help') }}</p>

            @if($listing->media->isNotEmpty())
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($listing->media as $media)
                        <div class="group relative aspect-square overflow-hidden rounded-xl border border-stone-200 bg-stone-100 dark:border-border dark:bg-muted">
                            <img src="{{ $media->url() }}" alt="" class="h-full w-full object-cover">

                            @if($loop->first)
                                <span class="absolute start-1.5 top-1.5 rounded-full bg-gradient-brand px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                    {{ __('seller.cover_photo') }}
                                </span>
                            @endif

                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 bg-black/60 p-1.5 opacity-0 transition group-hover:opacity-100">
                                @unless($loop->first)
                                    <form method="post" action="{{ route('seller.listings.media.cover', [$listing, $media]) }}">
                                        @csrf
                                        <button class="rounded-full bg-white/90 px-2 py-1 text-[10px] font-semibold text-leaf-800 hover:bg-white">
                                            {{ __('seller.make_cover') }}
                                        </button>
                                    </form>
                                @else
                                    <span></span>
                                @endunless

                                <form method="post" action="{{ route('seller.listings.media.destroy', [$listing, $media]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-full bg-white/90 px-2 py-1 text-[10px] font-semibold text-red-600 hover:bg-white">
                                        {{ __('seller.remove') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mb-4 text-sm text-amber-700 dark:text-amber-400">{{ __('seller.no_photos_yet') }}</p>
            @endif

            <form method="post" action="{{ route('seller.listings.media.store', $listing) }}" enctype="multipart/form-data"
                  x-data="{ names: [] }">
                @csrf
                <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed border-stone-300 px-4 py-6 text-center hover:border-leaf-400 dark:border-border dark:hover:border-primary/50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-6 w-6 text-stone-400 dark:text-muted-foreground">
                        <path d="M12 16V4m0 0 4 4m-4-4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-sm font-medium text-stone-600 dark:text-foreground" x-text="names.length ? names.join(', ') : '{{ __('seller.add_photos') }}'"></span>
                    <span class="text-xs text-stone-400 dark:text-muted-foreground">{{ __('seller.photo_constraints') }}</span>
                    <input type="file" name="photos[]" accept="image/*" multiple class="hidden"
                           x-on:change="names = Array.from($event.target.files).map(f => f.name)">
                </label>
                <button class="mt-3 rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                    {{ __('seller.upload_photos') }}
                </button>
            </form>
        </section>

        <form method="post" action="{{ route('seller.listings.update', $listing) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('seller.listings._form-fields')

            <button class="rounded-full bg-gradient-brand px-6 py-2.5 font-semibold text-white shadow-card hover:opacity-90">
                {{ __('seller.save_changes') }}
            </button>
        </form>

        <a href="{{ route('seller.listings.index') }}" class="inline-block text-sm text-leaf-700 underline dark:text-primary">
            {{ __('common.back') }}
        </a>
    </div>
@endsection

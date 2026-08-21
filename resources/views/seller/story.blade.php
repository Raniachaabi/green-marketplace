@extends('layouts.app')
@section('title', __('seller.nav_story'))

@section('content')
    <x-seller-nav active="story" />

    <h1 class="mb-2 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('seller.nav_story') }}</h1>
    <p class="mb-6 text-sm text-stone-500 dark:text-muted-foreground">{{ __('seller.story_intro') }}</p>

    <form method="post" action="{{ route('seller.story.update') }}" enctype="multipart/form-data"
          class="space-y-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-card dark:border-border dark:bg-card">
        @csrf
        @method('put')

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.cover_photo') }}</span>
            @if($user->cover_path)
                <img src="{{ $user->coverUrl() }}" alt="" class="mt-2 h-32 w-full rounded-xl object-cover">
            @endif
            <input type="file" name="cover" accept="image/*"
                   class="mt-2 block w-full text-sm text-stone-500 file:mr-3 file:rounded-full file:border-0 file:bg-leaf-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-leaf-700 dark:text-muted-foreground dark:file:bg-primary/15 dark:file:text-primary">
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.bio') }}</span>
            <textarea name="bio" rows="2" maxlength="1000"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">{{ old('bio', $user->bio) }}</textarea>
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.story_field') }}</span>
            <textarea name="story" rows="5" maxlength="4000"
                      placeholder="{{ __('seller.story_placeholder') }}"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">{{ old('story', $user->story) }}</textarea>
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.founding_year') }}</span>
                <input type="number" name="founding_year" min="1900" max="{{ now()->year }}"
                       value="{{ old('founding_year', $user->founding_year) }}"
                       class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
            </label>

            <label class="block text-sm">
                <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.production_method') }}</span>
                <input name="production_method" value="{{ old('production_method', $user->production_method) }}"
                       class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">
            </label>
        </div>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.mission') }}</span>
            <textarea name="mission" rows="2" maxlength="1000"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground">{{ old('mission', $user->mission) }}</textarea>
        </label>

        <button class="rounded-full bg-gradient-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
            {{ __('common.save') }}
        </button>
    </form>
@endsection

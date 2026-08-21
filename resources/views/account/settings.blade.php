@extends('layouts.app')
@section('title', __('account.settings_title'))

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-6 text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('account.settings_title') }}</h1>

        <form method="post" action="{{ route('settings.update') }}"
              class="space-y-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-card dark:border-border dark:bg-card">
            @csrf
            @method('put')

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">
                    {{ __('account.notification_preferences') }}
                </h2>

                <label class="flex items-start justify-between gap-4 py-2">
                    <span>
                        <span class="block font-medium text-leaf-900 dark:text-foreground">{{ __('account.notify_social') }}</span>
                        <span class="block text-xs text-stone-500 dark:text-muted-foreground">{{ __('account.notify_social_help') }}</span>
                    </span>
                    <input type="checkbox" name="notify_social" value="1" @checked($user->notify_social)
                           class="mt-1 h-5 w-5 shrink-0 rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                </label>

                <label class="flex items-start justify-between gap-4 py-2">
                    <span>
                        <span class="block font-medium text-leaf-900 dark:text-foreground">{{ __('account.notify_announcements') }}</span>
                        <span class="block text-xs text-stone-500 dark:text-muted-foreground">{{ __('account.notify_announcements_help') }}</span>
                    </span>
                    <input type="checkbox" name="notify_announcements" value="1" @checked($user->notify_announcements)
                           class="mt-1 h-5 w-5 shrink-0 rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
                </label>

                <p class="mt-3 text-xs text-stone-400 dark:text-muted-foreground">{{ __('account.notify_transactional_note') }}</p>
            </div>

            <button class="rounded-full bg-gradient-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                {{ __('common.save') }}
            </button>
        </form>
    </div>
@endsection

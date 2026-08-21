@extends('layouts.app')
@section('title', __('auth.register_title'))

@section('content')
    <div class="mx-auto flex min-h-[60vh] max-w-md flex-col justify-center py-10">
        <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-card dark:border-border dark:bg-card">
            <div class="mb-6 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-brand text-white shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                        <path d="M12 21c-4.5 0-8-3.5-8-8 0-6 6-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M12 21V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </span>
                <h1 class="mt-3 text-xl font-semibold text-leaf-900 dark:text-foreground">{{ __('auth.register_title') }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-muted-foreground">{{ __('auth.register_subtitle') }}</p>
            </div>

            <form method="post" action="{{ route('register.store') }}" class="space-y-4">
                @csrf

                <label class="block text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">{{ __('auth.full_name') }}</span>
                    <input name="full_name" value="{{ old('full_name') }}" required autofocus
                           class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                </label>

                <label class="block text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">{{ __('auth.phone') }}</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="+216 ..."
                           class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                </label>

                <label class="block text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">{{ __('auth.email') }}</span>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                </label>

                <label class="block text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">{{ __('auth.password') }}</span>
                    <input type="password" name="password" required minlength="8"
                           class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                </label>

                <label class="block text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">{{ __('auth.confirm_password') }}</span>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                </label>

                <button class="w-full rounded-full bg-gradient-brand px-6 py-2.5 font-semibold text-white shadow-card hover:opacity-90">
                    {{ __('auth.register_submit') }}
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-stone-500 dark:text-muted-foreground">
                {{ __('auth.have_account') }}
                <a href="{{ route('login.show') }}" class="font-semibold text-leaf-700 hover:underline dark:text-primary">
                    {{ __('auth.submit') }}
                </a>
            </p>
        </div>

        <a href="{{ route('home') }}" class="mt-4 text-center text-sm text-leaf-700 underline dark:text-primary">
            {{ __('auth.back_to_home') }}
        </a>
    </div>
@endsection

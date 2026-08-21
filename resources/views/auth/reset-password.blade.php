@extends('layouts.app')
@section('title', __('auth.reset_password_title'))

@section('content')
    <div class="mx-auto flex min-h-[60vh] max-w-md flex-col justify-center">
        <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-card dark:border-border dark:bg-card">
            <div class="mb-6 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-brand text-white shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                        <path d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm1-10V7a5 5 0 0 1 10 0v2"
                              stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h1 class="mt-3 text-xl font-semibold text-leaf-900 dark:text-foreground">{{ __('auth.reset_password_title') }}</h1>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <label class="block text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">{{ __('auth.email') }}</span>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus
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
                    {{ __('auth.reset_password_title') }}
                </button>
            </form>
        </div>

        <a href="{{ route('login.show') }}" class="mt-4 text-center text-sm text-leaf-700 underline dark:text-primary">
            {{ __('auth.back_to_login') }}
        </a>
    </div>
@endsection

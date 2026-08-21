<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ $dir ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Applied before first paint to avoid a light/dark flash on load.
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    NFR-02 — RTL is handled by the dir attribute plus logical properties
    (ms-/me-/ps-/pe-, start/end) everywhere in these templates. There is
    deliberately no ml-/mr- in this codebase: that is what makes Arabic a
    one-attribute switch instead of a month of work.
--}}
<body class="min-h-screen bg-stone-50 font-sans text-leaf-900 antialiased dark:bg-background dark:text-foreground">
<header class="sticky top-0 z-30 border-b border-stone-200/80 bg-white/90 backdrop-blur dark:border-border dark:bg-background/90">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:gap-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 text-lg font-extrabold tracking-tight text-leaf-800 dark:text-foreground">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-brand text-white shadow-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                    <path d="M12 21c-4.5 0-8-3.5-8-8 0-6 6-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M12 21V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="hidden sm:inline">{{ config('app.name') }}</span>
        </a>

        <form action="{{ route('catalog.index') }}" method="get" class="min-w-0 flex-1">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-stone-400 dark:text-muted-foreground">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
                    <path d="m20 20-3.2-3.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('common.search_placeholder') }}"
                       class="w-full rounded-full border-stone-200 bg-stone-100/70 ps-10 text-sm placeholder:text-stone-400 focus:border-leaf-500 focus:bg-white focus:ring-leaf-500 dark:border-border dark:bg-muted dark:text-foreground dark:placeholder:text-muted-foreground dark:focus:bg-card dark:focus:ring-ring">
            </div>
        </form>

        <nav class="flex items-center gap-1 text-sm sm:gap-2">
            <a href="{{ route('catalog.index') }}"
               class="hidden rounded-full px-3 py-2 font-medium text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 sm:inline-block dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground">
                {{ __('common.catalogue') }}
            </a>

            @auth
                <a href="{{ route('seller.listings.index') }}"
                   class="hidden rounded-full px-3 py-2 font-medium text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 sm:inline-block dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground">
                    {{ __('common.selling') }}
                </a>
                <a href="{{ route('orders.index') }}"
                   class="hidden rounded-full px-3 py-2 font-medium text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 sm:inline-block dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground">
                    {{ __('common.my_orders') }}
                </a>
            @else
                <a href="{{ url('/admin/login') }}"
                   class="hidden rounded-full px-3 py-2 font-medium text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 sm:inline-block dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground">
                    {{ __('common.sign_in') }}
                </a>
            @endauth

            <a href="{{ route('cart.show') }}"
               class="relative flex h-10 w-10 items-center justify-center rounded-full text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground"
               aria-label="{{ __('common.cart') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                    <path d="M3 4h2l1.4 10.4a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.6L20 8H6"
                          stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9.5" cy="20" r="1.4" fill="currentColor"/>
                    <circle cx="17" cy="20" r="1.4" fill="currentColor"/>
                </svg>
                @php($cartCount = app(\App\Services\Cart::class)->count())
                @if($cartCount)
                    <span class="absolute -top-0.5 -end-0.5 flex h-[18px] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-none text-white">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>

            {{-- Dark mode toggle — persisted per browser, applied before paint by the inline script in <head>. --}}
            <button type="button"
                    x-data
                    x-on:click="
                        document.documentElement.classList.toggle('dark');
                        localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                    "
                    class="flex h-10 w-10 items-center justify-center rounded-full text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground"
                    aria-label="{{ __('common.toggle_theme') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5 dark:hidden">
                    <path d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.4-6.4-1.4 1.4M7 17.4l-1.4 1.4m0-13.8L7 6.6m10.4 10.4-1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"
                          stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="hidden h-5 w-5 dark:block">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <span class="ms-1 hidden items-center gap-1 rounded-full bg-stone-100 p-1 text-xs font-medium text-stone-500 sm:flex dark:bg-muted dark:text-muted-foreground">
                @foreach(config('marketplace.locales') as $code => $meta)
                    <a href="{{ route('locale.switch', $code) }}"
                       class="rounded-full px-2 py-1 {{ app()->getLocale() === $code ? 'bg-white text-leaf-700 shadow-sm dark:bg-card dark:text-primary' : 'hover:text-leaf-600 dark:hover:text-foreground' }}">
                        {{ strtoupper($code) }}
                    </a>
                @endforeach
            </span>
        </nav>
    </div>

    <div class="border-t border-stone-100 bg-stone-50/60 sm:hidden dark:border-border dark:bg-muted/40">
        <div class="mx-auto flex max-w-7xl items-center gap-4 overflow-x-auto px-4 py-2 text-xs font-medium text-stone-600 dark:text-muted-foreground">
            <a href="{{ route('catalog.index') }}" class="shrink-0 hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.catalogue') }}</a>
            @auth
                <a href="{{ route('seller.listings.index') }}" class="shrink-0 hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.selling') }}</a>
                <a href="{{ route('orders.index') }}" class="shrink-0 hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.my_orders') }}</a>
            @else
                <a href="{{ url('/admin/login') }}" class="shrink-0 hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.sign_in') }}</a>
            @endauth
        </div>
    </div>
</header>

@if(session('status'))
    <div class="mx-auto mt-4 max-w-7xl px-4">
        <div class="flex items-center gap-2 rounded-xl bg-leaf-50 px-4 py-3 text-sm text-leaf-800 dark:bg-primary/10 dark:text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-4 w-4 shrink-0">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                <path d="m8.5 12.5 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ session('status') }}
        </div>
    </div>
@endif

@if($errors->any())
    <div class="mx-auto mt-4 max-w-7xl px-4">
        <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-destructive/10 dark:text-destructive">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif

<main class="mx-auto max-w-7xl px-4 py-8">
    @yield('content')
</main>

<footer class="mt-20 border-t border-stone-200 bg-white dark:border-border dark:bg-background">
    <div class="mx-auto max-w-7xl px-4 py-12">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2 text-lg font-extrabold text-leaf-800 dark:text-foreground">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-brand text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-[18px] w-[18px]">
                            <path d="M12 21c-4.5 0-8-3.5-8-8 0-6 6-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="M12 21V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </span>
                    {{ config('app.name') }}
                </div>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-stone-500 dark:text-muted-foreground">
                    {{ __('common.footer_note') }}
                </p>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('common.catalogue') }}</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-600 dark:text-muted-foreground">
                    <li><a href="{{ route('catalog.index') }}" class="hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.catalogue') }}</a></li>
                    <li><a href="{{ route('cart.show') }}" class="hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.cart') }}</a></li>
                    <li><a href="{{ route('orders.index') }}" class="hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.my_orders') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-muted-foreground">{{ __('common.selling') }}</h3>
                <ul class="mt-3 space-y-2 text-sm text-stone-600 dark:text-muted-foreground">
                    <li><a href="{{ route('seller.onboarding') }}" class="hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.selling') }}</a></li>
                    <li><a href="{{ url('/admin/login') }}" class="hover:text-leaf-700 dark:hover:text-foreground">{{ __('common.sign_in') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-stone-100 pt-6 text-xs text-stone-400 sm:flex-row dark:border-border dark:text-muted-foreground">
            <span>© {{ now()->year }} {{ config('app.name') }}</span>
            <span class="flex items-center gap-1 rounded-full bg-stone-100 px-1 text-stone-500 dark:bg-muted dark:text-muted-foreground">
                @foreach(config('marketplace.locales') as $code => $meta)
                    <a href="{{ route('locale.switch', $code) }}"
                       class="rounded-full px-2 py-1 {{ app()->getLocale() === $code ? 'bg-white text-leaf-700 shadow-sm dark:bg-card dark:text-primary' : 'dark:hover:text-foreground' }}">
                        {{ strtoupper($code) }}
                    </a>
                @endforeach
            </span>
        </div>
    </div>
</footer>
</body>
</html>

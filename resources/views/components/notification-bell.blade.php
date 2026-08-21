@php
    $recentNotifications = auth()->user()->notifications()->latest()->take(6)->get();
    $unreadCount = $recentNotifications->whereNull('read_at')->count();
@endphp

<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
    <button type="button" x-on:click="open = ! open"
            class="relative flex h-10 w-10 items-center justify-center rounded-full text-stone-600 hover:bg-leaf-50 hover:text-leaf-700 dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-foreground"
            aria-label="{{ __('notifications.title') }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
            <path d="M6 9a6 6 0 1 1 12 0c0 4 1.5 5.5 1.5 5.5H4.5S6 13 6 9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M10 18.5a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        @if($unreadCount)
            <span class="absolute -top-0.5 -end-0.5 flex h-[18px] min-w-[1.125rem] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-none text-white">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute end-0 z-40 mt-2 w-80 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card-hover dark:border-border dark:bg-card">
        <div class="flex items-center justify-between border-b border-stone-100 px-4 py-2.5 dark:border-border">
            <span class="text-sm font-semibold text-leaf-900 dark:text-foreground">{{ __('notifications.title') }}</span>
            @if($unreadCount)
                <form method="post" action="{{ route('notifications.read_all') }}">
                    @csrf
                    <button class="text-xs font-medium text-leaf-700 hover:underline dark:text-primary">
                        {{ __('notifications.mark_all_read') }}
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-80 divide-y divide-stone-100 overflow-y-auto dark:divide-border">
            @forelse($recentNotifications as $notification)
                <form method="post" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-start gap-2 px-4 py-3 text-start text-sm hover:bg-stone-50 dark:hover:bg-accent
                                   {{ $notification->read_at ? 'text-stone-500 dark:text-muted-foreground' : 'text-leaf-900 dark:text-foreground' }}">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-primary' }}"></span>
                        <span>
                            {{ __($notification->data['message_key'], $notification->data['replace'] ?? []) }}
                            <span class="mt-0.5 block text-xs text-stone-400 dark:text-muted-foreground/70">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </span>
                    </button>
                </form>
            @empty
                <p class="px-4 py-6 text-center text-sm text-stone-400 dark:text-muted-foreground">{{ __('notifications.empty') }}</p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}"
           class="block border-t border-stone-100 px-4 py-2.5 text-center text-xs font-semibold text-leaf-700 hover:bg-stone-50 dark:border-border dark:text-primary dark:hover:bg-accent">
            {{ __('notifications.view_all') }}
        </a>
    </div>
</div>

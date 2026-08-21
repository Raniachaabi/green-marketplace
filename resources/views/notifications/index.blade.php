@extends('layouts.app')
@section('title', __('notifications.title'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-leaf-900 dark:text-foreground">{{ __('notifications.title') }}</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('settings.show') }}" class="text-sm font-medium text-stone-500 hover:underline dark:text-muted-foreground">
                    {{ __('account.notification_preferences') }}
                </a>
                @if($notifications->contains(fn ($n) => $n->read_at === null))
                    <form method="post" action="{{ route('notifications.read_all') }}">
                        @csrf
                        <button class="text-sm font-semibold text-leaf-700 hover:underline dark:text-primary">
                            {{ __('notifications.mark_all_read') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-2">
            @forelse($notifications as $notification)
                <form method="post" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-start gap-3 rounded-2xl border p-4 text-start shadow-card transition
                                   {{ $notification->read_at
                                        ? 'border-stone-200 bg-white dark:border-border dark:bg-card'
                                        : 'border-leaf-200 bg-leaf-50 dark:border-primary/30 dark:bg-primary/10' }}">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-primary' }}"></span>
                        <span class="text-sm text-leaf-900 dark:text-foreground">
                            {{ __($notification->data['message_key'], $notification->data['replace'] ?? []) }}
                            <span class="mt-1 block text-xs text-stone-400 dark:text-muted-foreground">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center dark:border-border dark:bg-muted/40">
                    <p class="text-sm text-stone-500 dark:text-muted-foreground">{{ __('notifications.empty') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $notifications->links() }}</div>
    </div>
@endsection

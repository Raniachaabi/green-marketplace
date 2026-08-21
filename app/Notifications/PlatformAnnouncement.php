<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/** Phase 2 §1 — admin-broadcast platform announcements. */
class PlatformAnnouncement extends Notification
{
    public function __construct(private readonly string $title, private readonly string $body) {}

    public function via(object $notifiable): array
    {
        return ($notifiable instanceof User && ! $notifiable->wantsAnnouncements()) ? [] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.platform_announcement',
            'replace' => ['title' => $this->title, 'body' => $this->body],
            'url' => route('notifications.index'),
        ];
    }
}

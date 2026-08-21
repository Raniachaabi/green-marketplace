<?php

namespace App\Notifications;

use App\Models\Credential;
use Illuminate\Notifications\Notification;

class CredentialExpiringSoon extends Notification
{
    public function __construct(private readonly Credential $credential, private readonly int $daysLeft) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.credential_expiring_soon',
            'replace' => ['type' => $this->credential->credential_type_code, 'days' => $this->daysLeft],
            'url' => route('seller.onboarding'),
        ];
    }
}

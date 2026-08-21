<?php

namespace App\Notifications;

use App\Models\Credential;
use Illuminate\Notifications\Notification;

class CredentialExpired extends Notification
{
    public function __construct(private readonly Credential $credential) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.credential_expired',
            'replace' => ['type' => $this->credential->credential_type_code],
            'url' => route('seller.onboarding'),
        ];
    }
}

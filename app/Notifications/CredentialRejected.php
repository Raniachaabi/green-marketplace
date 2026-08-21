<?php

namespace App\Notifications;

use App\Models\Credential;
use Illuminate\Notifications\Notification;

class CredentialRejected extends Notification
{
    public function __construct(private readonly Credential $credential, private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.credential_rejected',
            'replace' => ['type' => $this->credential->credential_type_code, 'reason' => $this->reason],
            'url' => route('seller.onboarding'),
        ];
    }
}

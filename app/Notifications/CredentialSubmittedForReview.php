<?php

namespace App\Notifications;

use App\Models\Credential;
use Illuminate\Notifications\Notification;

/** Admin notification — a seller submitted a credential (this is the "new seller registration" / "verification request" signal in this app's architecture). */
class CredentialSubmittedForReview extends Notification
{
    public function __construct(private readonly Credential $credential) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.credential_submitted',
            'replace' => [
                'holder' => $this->credential->holder()?->displayName() ?? '—',
                'type' => $this->credential->credential_type_code,
            ],
            'url' => route('filament.admin.resources.credentials.index'),
        ];
    }
}

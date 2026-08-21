<?php

namespace App\Notifications;

use App\Models\ReviewReport;
use Illuminate\Notifications\Notification;

/** Admin notification — a review has been flagged by a buyer. */
class ReviewReported extends Notification
{
    public function __construct(private readonly ReviewReport $report) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.review_reported',
            'replace' => ['reason' => $this->report->reason],
            'url' => route('filament.admin.resources.reviews.index'),
        ];
    }
}

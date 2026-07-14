<?php

namespace App\Observers;

use App\Models\ReviewAlert;
use App\Models\User;
use App\Notifications\BadReviewAlertNotification;
use Illuminate\Support\Facades\Notification;

class ReviewAlertObserver
{
    public function created(ReviewAlert $alert): void
    {
        $recipients = User::role(config('reviews.notifications.recipient_roles', []))->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BadReviewAlertNotification($alert));

        $alert->forceFill([
            'notified_at' => now(),
            'notified_channels' => config('reviews.notifications.channels'),
        ])->saveQuietly();
    }
}

<?php

namespace App\Notifications;

use App\Models\ReviewAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;

class BadReviewAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReviewAlert $alert) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('reviews.notifications.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->alert->review;
        $app = $review->storeListing->app;

        return (new MailMessage)
            ->subject("Bad review alert: {$app->name} ({$review->rating}★)")
            ->line("A {$review->rating}-star review needs attention on {$app->name} ({$review->storeListing->platform->label()}).")
            ->line('"'.str($review->body)->limit(200).'" — '.($review->author_name ?? 'Anonymous'))
            ->line('Reason: '.$this->alert->reason->label().' · Severity: '.$this->alert->severity)
            ->action('Open alert triage', route('reviews.alerts'))
            ->line('You are receiving this because you handle review responses.');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $review = $this->alert->review;
        $app = $review->storeListing->app;

        return (new SlackMessage)
            ->text(sprintf(
                ':rotating_light: %s review alert — %s (%s): "%s" — %s',
                $this->alert->severity === 'high' ? 'High' : 'Medium',
                $app->name,
                str_repeat('★', $review->rating),
                str($review->body)->limit(140),
                $review->author_name ?? 'Anonymous',
            ));
    }
}

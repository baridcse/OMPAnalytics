<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bad-review alert rules
    |--------------------------------------------------------------------------
    |
    | A review triggers an alert when its rating is at or below max_rating,
    | OR its sentiment label is negative. Severity: 1-star (or both signals)
    | => high, otherwise medium.
    |
    */

    'alerts' => [
        'max_rating' => (int) env('REVIEW_ALERT_MAX_RATING', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Channels used for BadReviewAlertNotification. Slack requires a webhook
    | URL in services.slack.notifications. Recipients are users holding any
    | of the listed roles.
    |
    */

    'notifications' => [
        'channels' => array_filter([
            'mail',
            env('SLACK_ALERT_WEBHOOK_URL') ? 'slack' : null,
        ]),
        'recipient_roles' => ['reviews-responder', 'admin'],
    ],

];

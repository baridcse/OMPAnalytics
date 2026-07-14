<?php

namespace App\Enums;

enum AlertReason: string
{
    case LowRating = 'low_rating';
    case NegativeSentiment = 'negative_sentiment';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::LowRating => 'Low rating',
            self::NegativeSentiment => 'Negative sentiment',
            self::Both => 'Low rating + negative sentiment',
        };
    }
}

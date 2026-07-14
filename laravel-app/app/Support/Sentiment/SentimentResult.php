<?php

namespace App\Support\Sentiment;

use App\Enums\SentimentLabel;

final readonly class SentimentResult
{
    public function __construct(
        public float $score,      // -1.0 (very negative) .. 1.0 (very positive)
        public SentimentLabel $label,
        public float $magnitude,  // strength of the signal, 0..1
    ) {}
}

<?php

namespace App\Support\Sentiment;

interface SentimentAnalyzer
{
    public function analyze(string $text): SentimentResult;

    /**
     * Identifier stored on the review row (e.g. "lexicon", "google_nl").
     */
    public function name(): string;
}

<?php

namespace App\Support\Sentiment;

use Illuminate\Support\Manager;

/**
 * Driver manager for sentiment analysis. The default "lexicon" driver is
 * offline and free; cloud drivers (Google NL, AWS Comprehend, LLM-based)
 * can be registered here later behind the same SentimentAnalyzer contract.
 */
class SentimentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('sentiment.driver', 'lexicon');
    }

    public function createLexiconDriver(): SentimentAnalyzer
    {
        return new LexiconSentimentAnalyzer;
    }
}

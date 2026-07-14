<?php

namespace App\Support\Sentiment;

use App\Enums\SentimentLabel;
use RuntimeException;

/**
 * Offline AFINN-style analyzer with a lexicon curated for app-store review
 * vocabulary. Deterministic and dependency-free: the default driver.
 *
 * Scoring: sum of matched word scores (with simple negation handling),
 * normalized by 5 * sqrt(matched words) and clamped to [-1, 1].
 */
class LexiconSentimentAnalyzer implements SentimentAnalyzer
{
    private const NEGATORS = ['not', 'no', 'never', "don't", "doesn't", "didn't", "can't", "won't", "isn't", "wasn't", 'dont', 'doesnt', 'didnt', 'cant', 'wont', 'isnt', 'wasnt'];

    /** @var array<string, int>|null */
    private static ?array $lexicon = null;

    public function analyze(string $text): SentimentResult
    {
        $lexicon = $this->lexicon();

        $tokens = preg_split('/[^a-z\'-]+/', mb_strtolower($text), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        $sum = 0;
        $matched = 0;

        foreach ($tokens as $i => $token) {
            if (! isset($lexicon[$token])) {
                continue;
            }

            $score = $lexicon[$token];

            if ($i > 0 && in_array($tokens[$i - 1], self::NEGATORS, true)) {
                $score = -$score;
            }

            $sum += $score;
            $matched++;
        }

        if ($matched === 0) {
            return new SentimentResult(0.0, SentimentLabel::Neutral, 0.0);
        }

        $score = max(-1.0, min(1.0, $sum / (5 * sqrt($matched))));

        return new SentimentResult(
            score: round($score, 4),
            label: $this->labelFor($score),
            magnitude: round(min(1.0, abs($sum) / (5 * $matched)), 4),
        );
    }

    public function name(): string
    {
        return 'lexicon';
    }

    private function labelFor(float $score): SentimentLabel
    {
        return match (true) {
            $score <= (float) config('sentiment.negative_threshold', -0.1) => SentimentLabel::Negative,
            $score >= (float) config('sentiment.positive_threshold', 0.1) => SentimentLabel::Positive,
            default => SentimentLabel::Neutral,
        };
    }

    /**
     * @return array<string, int>
     */
    private function lexicon(): array
    {
        if (self::$lexicon !== null) {
            return self::$lexicon;
        }

        $path = resource_path('lexicons/app-reviews.json');
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("Sentiment lexicon at [{$path}] is missing or invalid.");
        }

        unset($data['_comment']);

        return self::$lexicon = $data;
    }
}

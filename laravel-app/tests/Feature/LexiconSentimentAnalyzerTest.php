<?php

use App\Enums\SentimentLabel;
use App\Support\Sentiment\LexiconSentimentAnalyzer;

beforeEach(function () {
    $this->analyzer = new LexiconSentimentAnalyzer;
});

it('scores clearly positive review text as positive', function () {
    $result = $this->analyzer->analyze('Amazing app, works perfectly. Love the beautiful design!');

    expect($result->label)->toBe(SentimentLabel::Positive)
        ->and($result->score)->toBeGreaterThan(0.3);
});

it('scores clearly negative review text as negative', function () {
    $result = $this->analyzer->analyze('Terrible update, it crashes constantly. Total waste of time.');

    expect($result->label)->toBe(SentimentLabel::Negative)
        ->and($result->score)->toBeLessThan(-0.3);
});

it('treats text without sentiment words as neutral', function () {
    $result = $this->analyzer->analyze('The settings screen has three tabs.');

    expect($result->label)->toBe(SentimentLabel::Neutral)
        ->and($result->score)->toBe(0.0);
});

it('handles empty text', function () {
    $result = $this->analyzer->analyze('');

    expect($result->label)->toBe(SentimentLabel::Neutral)
        ->and($result->magnitude)->toBe(0.0);
});

it('inverts scores after a negator', function () {
    $positive = $this->analyzer->analyze('This app is good.');
    $negated = $this->analyzer->analyze('This app is not good.');

    expect($positive->score)->toBeGreaterThan(0)
        ->and($negated->score)->toBeLessThan(0);
});

it('is deterministic across runs', function () {
    $text = 'Great app but too many ads and it lags sometimes.';

    expect($this->analyzer->analyze($text)->score)
        ->toBe($this->analyzer->analyze($text)->score);
});

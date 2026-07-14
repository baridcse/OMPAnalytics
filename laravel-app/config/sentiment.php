<?php

return [

    // Which SentimentManager driver analyzes review text.
    'driver' => env('SENTIMENT_DRIVER', 'lexicon'),

    // Score thresholds mapping a -1..1 score to a label.
    'positive_threshold' => 0.1,
    'negative_threshold' => -0.1,

];

<?php

namespace App\Models;

use App\Enums\SentimentLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'review_created_at' => 'datetime',
            'review_updated_at' => 'datetime',
            'reply_at' => 'datetime',
            'sentiment_score' => 'float',
            'sentiment_label' => SentimentLabel::class,
            'sentiment_magnitude' => 'float',
            'analyzed_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }

    public function alert(): HasOne
    {
        return $this->hasOne(ReviewAlert::class);
    }

    public function scopeNeedsAnalysis(Builder $query): Builder
    {
        return $query->whereNull('analyzed_at');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
        'avg_engagement_time' => 'float',
    ];

    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }
}

<?php

namespace App\Models;

use App\Enums\AlertReason;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAlert extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'reason' => AlertReason::class,
        'status' => AlertStatus::class,
        'notified_at' => 'datetime',
        'notified_channels' => 'array',
    ];

    /** @return BelongsTo<Review, $this> */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /** @return BelongsTo<StoreListing, $this> */
    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', AlertStatus::Open);
    }
}

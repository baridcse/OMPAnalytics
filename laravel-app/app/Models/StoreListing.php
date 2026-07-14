<?php

namespace App\Models;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreListing extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'meta' => 'array',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function performanceDaily(): HasMany
    {
        return $this->hasMany(AppPerformanceDaily::class);
    }

    public function adRevenueDaily(): HasMany
    {
        return $this->hasMany(AdRevenueDaily::class);
    }

    public function analyticsDaily(): HasMany
    {
        return $this->hasMany(AnalyticsDaily::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}

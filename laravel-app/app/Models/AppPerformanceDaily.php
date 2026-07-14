<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppPerformanceDaily extends Model
{
    use HasFactory;

    protected $table = 'app_performance_daily';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'crash_rate' => 'float',
            'anr_rate' => 'float',
            'rating_avg' => 'float',
        ];
    }

    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }
}

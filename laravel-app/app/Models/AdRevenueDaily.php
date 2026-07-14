<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdRevenueDaily extends Model
{
    use HasFactory;

    protected $table = 'ad_revenue_daily';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'ctr' => 'float',
            'ecpm' => 'float',
            'estimated_revenue' => 'float',
        ];
    }

    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }
}

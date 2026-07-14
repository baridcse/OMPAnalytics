<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'sync_cursor' => 'array',
            'last_synced_at' => 'datetime',
            'last_sync_status' => SyncStatus::class,
        ];
    }

    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    /**
     * Resolve a credential value, following {"ref":"env","key":"..."} indirection
     * so raw secrets can live only in .env.
     */
    public function credential(string $key): ?string
    {
        $value = $this->credentials[$key] ?? null;

        if (is_array($value) && ($value['ref'] ?? null) === 'env') {
            return env($value['key']);
        }

        return $value;
    }
}

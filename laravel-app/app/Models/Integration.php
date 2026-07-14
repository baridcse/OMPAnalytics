<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Env;

class Integration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['credentials'];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted:array',
        'config' => 'array',
        'sync_cursor' => 'array',
        'last_synced_at' => 'datetime',
        'last_sync_status' => SyncStatus::class,
    ];

    /** @return BelongsTo<StoreListing, $this> */
    public function storeListing(): BelongsTo
    {
        return $this->belongsTo(StoreListing::class);
    }

    /** @return HasMany<SyncRun, $this> */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    /**
     * Resolve a credential value, following {"ref":"env","key":"..."} indirection
     * so raw secrets never live in the database. In production the referenced
     * keys must be real OS-level environment variables (config caching skips
     * .env parsing).
     */
    public function credential(string $key): ?string
    {
        $value = $this->credentials[$key] ?? null;

        if (is_array($value) && ($value['ref'] ?? null) === 'env') {
            return Env::get($value['key']);
        }

        return $value;
    }
}

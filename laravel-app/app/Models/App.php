<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class App extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function storeListings(): HasMany
    {
        return $this->hasMany(StoreListing::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, StoreListing::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}

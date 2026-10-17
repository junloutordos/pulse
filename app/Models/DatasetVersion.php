<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatasetVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checksums' => 'array',
            'reconciliation' => 'array',
            'issues' => 'array',
            'is_active' => 'boolean',
            'refreshed_at' => 'datetime',
        ];
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public static function activeOrFail(): self
    {
        return static::active() ?? abort(409, 'No active dataset.');
    }

    public function activate(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(MunicipalityIndicator::class);
    }

    public function neighbors(): HasMany
    {
        return $this->hasMany(MunicipalityNeighbor::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalityIndicator extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate_per_1000' => 'float',
            'attendance' => 'float',
            'change' => 'float',
            'c_reach' => 'float',
            'c_attendance' => 'float',
            'c_decline' => 'float',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'psgc', 'psgc');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlannerDecision extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['weights' => 'array'];
    }
}

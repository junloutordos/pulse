<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipalityNeighbor extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['km' => 'float'];
    }
}

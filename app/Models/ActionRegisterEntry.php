<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionRegisterEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['planned_on' => 'date', 'completed_on' => 'date'];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealResponse extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['service_date' => 'date'];
    }

}

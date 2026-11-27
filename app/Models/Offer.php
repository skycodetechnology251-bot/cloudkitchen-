<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['zone_ids' => 'array', 'days' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'new_customers_only' => 'boolean'];
    }
}

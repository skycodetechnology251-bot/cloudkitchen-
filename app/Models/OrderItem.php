<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['allergens' => 'array'];
    }

    public function order() { return $this->belongsTo(Order::class); }
    public function dish() { return $this->belongsTo(Dish::class); }
}

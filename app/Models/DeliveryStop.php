<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryStop extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }

    public function batch() { return $this->belongsTo(DeliveryBatch::class, 'delivery_batch_id'); }
    public function order() { return $this->belongsTo(Order::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBatch extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['pickup_code'];

    protected function casts(): array
    {
        return ['offer_expires_at' => 'datetime', 'picked_up_at' => 'datetime'];
    }

    public function stops() { return $this->hasMany(DeliveryStop::class)->orderBy('sequence'); }
    public function kitchen() { return $this->belongsTo(Kitchen::class); }
    public function rider() { return $this->belongsTo(User::class, 'rider_id'); }
}

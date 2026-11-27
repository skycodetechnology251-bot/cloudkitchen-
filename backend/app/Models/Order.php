<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['delivery_otp_hash', 'delivery_code'];

    protected function casts(): array
    {
        return ['address_snapshot' => 'array', 'service_date' => 'date', 'reserved_until' => 'datetime', 'delivery_code' => 'encrypted'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function kitchen() { return $this->belongsTo(Kitchen::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function events() { return $this->hasMany(OrderEvent::class); }
    public function payments() { return $this->morphMany(Payment::class, 'payable'); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    public function refunds() { return $this->hasMany(Refund::class); }
    public function stop() { return $this->hasOne(DeliveryStop::class); }
    public function offer() { return $this->belongsTo(Offer::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }

    public function rupees(string $field = 'total_paise'): string
    {
        return number_format($this->{$field} / 100, 2);
    }
}

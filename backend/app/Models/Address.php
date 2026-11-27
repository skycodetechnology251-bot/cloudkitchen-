<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $guarded = ['id', 'user_id'];

    protected function casts(): array
    {
        return ['lat' => 'float', 'lng' => 'float', 'is_default' => 'boolean', 'archived_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function oneLine(): string
    {
        return collect([$this->flat, $this->building, $this->street, $this->area, $this->city, $this->state.' '.$this->pincode])
            ->filter()->implode(', ');
    }

    /** Haversine distance in metres. */
    public function distanceTo(float $lat, float $lng): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat - $this->lat);
        $dLng = deg2rad($lng - $this->lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    public function snapshot(): array
    {
        return $this->only(['label', 'flat', 'building', 'street', 'area', 'landmark', 'city', 'state', 'country', 'pincode', 'lat', 'lng', 'receiver_name', 'receiver_phone', 'instructions']);
    }
}

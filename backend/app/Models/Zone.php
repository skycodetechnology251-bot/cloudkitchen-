<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['polygon' => 'array', 'went_live_at' => 'datetime'];
    }

    public function kitchens() { return $this->belongsToMany(Kitchen::class); }

    /** Ray-casting point-in-polygon test. Polygon is a list of [lat, lng]. */
    public function contains(float $lat, float $lng): bool
    {
        $points = $this->polygon ?? [];
        $inside = false;
        $n = count($points);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$yi, $xi] = $points[$i];
            [$yj, $xj] = $points[$j];
            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersect) {
                $inside = ! $inside;
            }
        }
        return $inside;
    }
}

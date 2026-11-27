<?php

namespace App\Services;

use App\Models\Kitchen;
use App\Models\Zone;
use Illuminate\Support\Collection;

class ServiceabilityService
{
    public function zoneFor(float $lat, float $lng): ?Zone
    {
        return Zone::whereIn('status', ['live', 'testing'])->get()->first(fn (Zone $z) => $z->contains($lat, $lng));
    }

    /** Every live/testing zone the point falls in (zones of different branches may overlap). */
    public function zonesFor(float $lat, float $lng): Collection
    {
        return Zone::whereIn('status', ['live', 'testing'])->get()->filter(fn (Zone $z) => $z->contains($lat, $lng))->values();
    }

    /** Open branches that serve the point through any of its zones, nearest first, each with distance_m. */
    public function kitchensFor(float $lat, float $lng): Collection
    {
        $zoneIds = $this->zonesFor($lat, $lng)->pluck('id');
        if ($zoneIds->isEmpty()) {
            return collect();
        }
        return Kitchen::where('is_open', true)->whereHas('zones', fn ($q) => $q->whereIn('zones.id', $zoneIds))->get()
            ->map(function (Kitchen $k) use ($lat, $lng) {
                $k->distance_m = (int) $this->distance($lat, $lng, $k->lat, $k->lng);
                return $k;
            })
            ->filter(fn ($k) => $k->distance_m <= $k->service_radius_m)
            ->sortBy('distance_m')->values();
    }

    public function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }
}

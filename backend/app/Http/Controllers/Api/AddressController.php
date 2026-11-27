<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\ServiceabilityService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    private function rules(): array
    {
        return [
            'label' => 'required|string|max:30',
            'flat' => 'required|string|max:80',
            'building' => 'nullable|string|max:120',
            'street' => 'required|string|max:160',
            'area' => 'required|string|max:120',
            'landmark' => 'nullable|string|max:120',
            'city' => 'required|string|max:60',
            'state' => 'required|string|max:60',
            'country' => 'required|string|max:60',
            'pincode' => ['required', 'regex:/^\d{6}$/'],
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'receiver_name' => 'nullable|string|max:80',
            'receiver_phone' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'instructions' => 'nullable|string|max:200',
            'is_default' => 'boolean',
        ];
    }

    public function index(Request $r, ServiceabilityService $geo)
    {
        return response()->json($r->user()->addresses()->latest()->get()->map(fn (Address $a) => $this->present($a, $geo)));
    }

    public function store(Request $r, ServiceabilityService $geo)
    {
        $a = $r->user()->addresses()->create($r->validate($this->rules()));
        $this->exclusiveDefault($a);
        return response()->json($this->present($a, $geo), 201);
    }

    public function update(Request $r, Address $address, ServiceabilityService $geo)
    {
        abort_unless($address->user_id === $r->user()->id, 404);
        $address->update($r->validate($this->rules()));
        $this->exclusiveDefault($address);
        return response()->json($this->present($address, $geo));
    }

    public function destroy(Request $r, Address $address)
    {
        abort_unless($address->user_id === $r->user()->id, 404);
        $address->update(['archived_at' => now()]); // keeps order snapshots intact
        return response()->json(['ok' => true]);
    }

    /** Given the phone's GPS, return the saved address the customer is standing at, if any. */
    public function detect(Request $r, ServiceabilityService $geo)
    {
        $data = $r->validate(['lat' => 'required|numeric', 'lng' => 'required|numeric']);
        $radius = config('dailytable.delivery.auto_detect_radius_m');
        $near = $r->user()->addresses()->get()
            ->map(fn ($a) => [$a, $a->distanceTo($data['lat'], $data['lng'])])
            ->filter(fn ($p) => $p[1] <= $radius)->sortBy(1)->first();
        return response()->json([
            'address' => $near ? $this->present($near[0], $geo) + ['distance_m' => (int) $near[1]] : null,
            'serviceable_here' => (bool) $geo->zoneFor($data['lat'], $data['lng']),
        ]);
    }

    private function present(Address $a, ServiceabilityService $geo): array
    {
        $zone = $geo->zoneFor($a->lat, $a->lng);
        return $a->toArray() + ['one_line' => $a->oneLine(), 'zone' => $zone ? ['id' => $zone->id, 'name' => $zone->name, 'status' => $zone->status] : null];
    }

    private function exclusiveDefault(Address $a): void
    {
        if ($a->is_default) {
            Address::where('user_id', $a->user_id)->where('id', '!=', $a->id)->update(['is_default' => false]);
        }
    }
}

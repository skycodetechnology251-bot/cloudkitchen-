<?php

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\Kitchen;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Server-side quote. The app never sends prices; it sends daily_menu ids and quantities.
 */
class PricingService
{
    public function __construct(private GstCalculator $gst, private PointsService $points) {}

    /**
     * @param array<int,int> $items daily_menu_id => quantity
     */
    public function quote(?User $user, Kitchen $kitchen, array $items, array $opts = []): array
    {
        $orderType = $opts['order_type'] ?? 'delivery';
        $isPos = ($opts['channel'] ?? 'app') === 'pos';
        if (! $items) {
            throw ValidationException::withMessages(['items' => 'Your basket is empty.']);
        }

        $menus = DailyMenu::with('dish')->whereIn('id', array_keys($items))->where('kitchen_id', $kitchen->id)->get()->keyBy('id');
        $lines = [];
        $warnings = [];
        $subtotal = 0;
        $profile = $user?->healthProfile;
        $date = null;
        $meal = null;

        foreach ($items as $menuId => $qty) {
            $qty = (int) $qty;
            $m = $menus->get($menuId);
            if (! $m || $qty < 1) {
                throw ValidationException::withMessages(['items' => 'A dish in your basket is no longer on this menu.']);
            }
            $date ??= $m->service_date;
            $meal ??= $m->meal;
            if ($m->meal !== $meal) {
                throw ValidationException::withMessages(['items' => 'Please order one meal at a time (for example lunch or dinner).']);
            }
            if (! $m->service_date->equalTo($date)) {
                throw ValidationException::withMessages(['items' => 'All dishes must be for the same day.']);
            }
            if ($m->status !== 'open' || $m->available() < $qty) {
                throw ValidationException::withMessages(['items' => $m->dish->name.' is sold out or has fewer portions left.']);
            }
            if (! $isPos && $this->pastCutoff($m)) {
                throw ValidationException::withMessages(['items' => 'Ordering for this meal closed at '.substr($m->cutoff_at, 0, 5).'.']);
            }
            $line = $m->price_paise * $qty;
            $subtotal += $line;
            $lines[] = ['daily_menu_id' => $m->id, 'dish_id' => $m->dish_id, 'name' => $m->dish->name, 'sac' => $m->dish->sac, 'quantity' => $qty, 'unit_price_paise' => $m->price_paise, 'line_total_paise' => $line, 'allergens' => $m->dish->allergens ?? []];

            if ($profile) {
                $hit = array_values(array_intersect(array_map('strtolower', $profile->allergies ?? []), array_map('strtolower', $m->dish->allergens ?? [])));
                if ($hit) {
                    $warnings[] = ['type' => 'allergy', 'dish' => $m->dish->name, 'allergens' => $hit];
                }
                if ($profile->diet === 'veg' && $m->dish->diet !== 'veg') {
                    $warnings[] = ['type' => 'diet', 'dish' => $m->dish->name];
                }
            }
        }

        $delivery = $orderType === 'delivery' ? config('dailytable.ordering.delivery_fee_paise') : 0;
        [$discount, $offer, $freeDelivery] = $this->applyOffer($user, $kitchen, $opts['offer_code'] ?? null, $subtotal, $profile?->diet);
        if ($freeDelivery) {
            $delivery = 0;
        }
        if ($isPos && ! empty($opts['discount_percent'])) {
            $discount = min($subtotal, $discount + (int) round(($subtotal - $discount) * (int) $opts['discount_percent'] / 100));
        }

        $pointsUsed = 0;
        $pointsValue = 0;
        if ($user && ! empty($opts['use_points'])) {
            $r = $this->points->redeemable($user, $subtotal - $discount);
            [$pointsUsed, $pointsValue] = [$r['points'], $r['value_paise']];
        }

        $beforeTax = max(0, $subtotal - $discount - $pointsValue) + $delivery;
        $taxable = config('dailytable.gst.prices_include_tax') ? $this->gst->taxableFromInclusive($beforeTax) : $beforeTax;
        $tax = $this->gst->forTaxable($taxable);

        return [
            'kitchen_id' => $kitchen->id,
            'service_date' => $date?->toDateString(),
            'meal' => $meal ?? 'lunch',
            'lines' => $lines,
            'subtotal_paise' => $subtotal,
            'discount_paise' => $discount + $pointsValue,
            'offer_discount_paise' => $discount,
            'points_used' => $pointsUsed,
            'points_value_paise' => $pointsValue,
            'delivery_paise' => $delivery,
            'taxable_paise' => $tax['taxable'],
            'cgst_paise' => $tax['cgst'],
            'sgst_paise' => $tax['sgst'],
            'round_off_paise' => $tax['round_off'],
            'total_paise' => $tax['total'],
            'offer' => $offer ? ['id' => $offer->id, 'code' => $offer->code, 'title' => $offer->title] : null,
            'warnings' => $warnings,
        ];
    }

    private function pastCutoff(DailyMenu $m): bool
    {
        $tz = config('dailytable.timezone');
        $cutoff = \Carbon\Carbon::parse($m->service_date->toDateString().' '.$m->cutoff_at, $tz);
        return now($tz)->greaterThan($cutoff);
    }

    private function applyOffer(?User $user, Kitchen $kitchen, ?string $code, int $subtotal, ?string $diet): array
    {
        if (! $code) {
            return [0, null, false];
        }
        $offer = Offer::where('code', strtoupper(trim($code)))->where('is_active', true)->first();
        $fail = fn ($m) => throw ValidationException::withMessages(['offer_code' => $m]);
        if (! $offer) {
            $fail('This code is not valid.');
        }
        if (($offer->starts_at && $offer->starts_at->isFuture()) || ($offer->ends_at && $offer->ends_at->isPast())) {
            $fail('This offer is not running right now.');
        }
        if ($offer->days && ! in_array(now(config('dailytable.timezone'))->dayOfWeekIso, $offer->days)) {
            $fail('This offer is not valid today.');
        }
        if ($subtotal < $offer->min_order_paise) {
            $fail('Add ₹'.number_format(($offer->min_order_paise - $subtotal) / 100).' more to use this offer.');
        }
        if ($offer->total_uses && $offer->used >= $offer->total_uses) {
            $fail('This offer has been fully used.');
        }
        if ($offer->zone_ids && ! $kitchen->zones()->whereIn('zones.id', $offer->zone_ids)->exists()) {
            $fail('This offer is not available in your area.');
        }
        if ($user) {
            $used = $user->orders()->where('offer_id', $offer->id)->where('payment_status', 'paid')->count();
            if ($used >= $offer->uses_per_customer) {
                $fail('You have already used this offer.');
            }
            if ($offer->new_customers_only && $user->orders()->where('payment_status', 'paid')->exists()) {
                $fail('This offer is for your first order.');
            }
        }
        $discount = match ($offer->type) {
            'percent' => (int) round($subtotal * $offer->value / 100),
            'flat' => (int) $offer->value,
            default => 0,
        };
        if ($offer->max_discount_paise) {
            $discount = min($discount, $offer->max_discount_paise);
        }
        return [min($discount, $subtotal), $offer, $offer->type === 'free_delivery'];
    }
}

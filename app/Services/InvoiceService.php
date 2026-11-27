<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/** Issues one GST tax invoice per paid order with a gap-free series per kitchen per financial year. */
class InvoiceService
{
    public static function financialYear(?\DateTimeInterface $at = null): string
    {
        $d = $at ? \Carbon\Carbon::instance($at) : now(config('dailytable.timezone'));
        $start = $d->month >= 4 ? $d->year : $d->year - 1;
        return substr((string) $start, 2).'-'.substr((string) ($start + 1), 2);
    }

    public function issue(Order $order): Invoice
    {
        if ($order->invoice) {
            return $order->invoice;
        }
        return DB::transaction(function () use ($order) {
            $kitchen = $order->kitchen;
            $fy = self::financialYear();
            $series = config('dailytable.gst.invoice_prefix').'/'.$kitchen->code.'/'.$fy;
            $seq = (int) Invoice::where('series', $series)->lockForUpdate()->max('sequence') + 1;
            $order->loadMissing('items', 'user', 'payments');

            return Invoice::create([
                'order_id' => $order->id,
                'kitchen_id' => $kitchen->id,
                'series' => $series,
                'sequence' => $seq,
                'financial_year' => $fy,
                'number' => $series.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                'issued_at' => now(),
                'snapshot' => [
                    'seller' => ['name' => $kitchen->legal_name ?: config('dailytable.brand'), 'kitchen' => $kitchen->name, 'address' => $kitchen->address, 'gstin' => $kitchen->gstin, 'fssai' => $kitchen->fssai],
                    'buyer' => ['name' => $order->user?->name ?? 'Walk-in customer', 'phone' => $order->user?->phone, 'address' => $order->address_snapshot],
                    'place_of_supply' => config('dailytable.gst.place_of_supply'),
                    'rate' => config('dailytable.gst.rate_percent'),
                    'order' => ['number' => $order->number, 'channel' => $order->channel, 'type' => $order->order_type, 'cashier' => $order->cashier?->name],
                    'lines' => $order->items->map->only(['name', 'sac', 'quantity', 'unit_price_paise', 'line_total_paise'])->values(),
                    'totals' => $order->only(['subtotal_paise', 'discount_paise', 'delivery_paise', 'taxable_paise', 'cgst_paise', 'sgst_paise', 'round_off_paise', 'total_paise', 'wallet_paise']),
                    'payments' => $order->payments->where('status', 'captured')->map->only(['method', 'amount_paise', 'gateway_payment_id'])->values(),
                ],
            ]);
        });
    }

    public static function amountInWords(int $rupees): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $f = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            return 'Rupees '.ucwords(str_replace('-', ' ', $f->format($rupees))).' only';
        }
        return 'Rupees '.number_format($rupees).' only';
    }
}

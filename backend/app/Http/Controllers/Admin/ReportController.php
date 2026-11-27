<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    private function range(Request $r): array
    {
        $to = Carbon::parse($r->input('to', now('Asia/Kolkata')->endOfMonth()->toDateString()))->endOfDay();
        $from = Carbon::parse($r->input('from', $to->copy()->subMonths(5)->startOfMonth()->toDateString()))->startOfDay();
        return [$from, $to];
    }

    public function index(Request $r)
    {
        [$from, $to] = $this->range($r);
        $kitchenId = $r->input('kitchen');
        $orders = Order::where('payment_status', '!=', 'pending')->whereBetween('created_at', [$from, $to])->when($kitchenId, fn ($q) => $q->where('kitchen_id', $kitchenId));
        $expenses = Expense::whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])->when($kitchenId, fn ($q) => $q->where('kitchen_id', $kitchenId));

        $monthly = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor <= $to) {
            $key = $cursor->format('Y-m');
            $monthly[$key] = ['label' => $cursor->format('M Y'), 'income' => 0, 'expense' => 0, 'cgst' => 0, 'sgst' => 0, 'taxable' => 0, 'refunds' => 0];
            $cursor->addMonth();
        }
        foreach ((clone $orders)->whereIn('payment_status', ['paid', 'partially_refunded', 'refunded'])->get(['created_at', 'taxable_paise', 'cgst_paise', 'sgst_paise']) as $o) {
            $k = $o->created_at->format('Y-m');
            if (isset($monthly[$k])) {
                $monthly[$k]['income'] += $o->taxable_paise;
                $monthly[$k]['taxable'] += $o->taxable_paise;
                $monthly[$k]['cgst'] += $o->cgst_paise;
                $monthly[$k]['sgst'] += $o->sgst_paise;
            }
        }
        foreach ((clone $expenses)->get(['spent_on', 'amount_paise']) as $e) {
            $k = $e->spent_on->format('Y-m');
            if (isset($monthly[$k])) {
                $monthly[$k]['expense'] += $e->amount_paise;
            }
        }
        $categories = (clone $expenses)->selectRaw('category, SUM(amount_paise) as total')->groupBy('category')->orderByDesc('total')->pluck('total', 'category');
        $byKitchen = Kitchen::get()->map(fn ($k) => [
            'name' => $k->name,
            'income' => Order::where('kitchen_id', $k->id)->whereIn('payment_status', ['paid', 'partially_refunded'])->whereBetween('created_at', [$from, $to])->sum('taxable_paise'),
            'expense' => Expense::where('kitchen_id', $k->id)->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])->sum('amount_paise'),
            'orders' => Order::where('kitchen_id', $k->id)->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to])->count(),
        ]);
        $liabilities = ['wallets' => Wallet::sum('balance_paise'), 'points' => (int) PointTransaction::where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->sum('points')];

        return view('admin.reports', compact('monthly', 'categories', 'byKitchen', 'liabilities', 'from', 'to') + ['kitchens' => Kitchen::pluck('name', 'id'), 'kitchenId' => $kitchenId]);
    }

    /** Invoice-level GST register for the accountant (GSTR-1 preparation). */
    public function gstCsv(Request $r)
    {
        [$from, $to] = $this->range($r);
        $rows = Invoice::whereBetween('issued_at', [$from, $to])->orderBy('issued_at')->get();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Invoice number', 'Date', 'Kitchen GSTIN', 'Buyer', 'Place of supply', 'Taxable', 'CGST', 'SGST', 'Total']);
        foreach ($rows as $inv) {
            $t = $inv->snapshot['totals'];
            fputcsv($out, [$inv->number, $inv->issued_at->format('d-m-Y'), $inv->snapshot['seller']['gstin'] ?? '', $inv->snapshot['buyer']['name'] ?? '', $inv->snapshot['place_of_supply'] ?? '',
                $t['taxable_paise'] / 100, $t['cgst_paise'] / 100, $t['sgst_paise'] / 100, $t['total_paise'] / 100]);
        }
        rewind($out);
        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="gst-register-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv"']);
    }

    public function invoice(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }
}

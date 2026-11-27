<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function show(Request $r)
    {
        $w = $r->user()->walletOrCreate();
        $avg = config('dailytable.ordering.average_lunch_paise');
        return response()->json([
            'balance_paise' => $w->balance_paise,
            'lunches_covered' => intdiv($w->balance_paise, $avg),
            'low' => $w->isLow(),
            'low_balance_paise' => $w->low_balance_paise,
            'pay_first' => $w->pay_first,
            'auto_topup' => $w->auto_topup,
            'auto_topup_paise' => $w->auto_topup_paise,
            'presets_paise' => config('dailytable.wallet.presets_paise'),
            'points' => $r->user()->pointsBalance(),
            'transactions' => $w->transactions()->limit(30)->get(['id', 'type', 'amount_paise', 'balance_after_paise', 'note', 'created_at']),
        ]);
    }

    public function settings(Request $r)
    {
        $d = $r->validate(['low_balance_paise' => 'sometimes|integer|in:15000,30000,50000', 'pay_first' => 'sometimes|boolean', 'auto_topup' => 'sometimes|boolean']);
        $r->user()->walletOrCreate()->update($d);
        return $this->show($r);
    }

    public function topup(Request $r, RazorpayService $rzp)
    {
        $cfg = config('dailytable.wallet');
        $d = $r->validate(['amount_paise' => 'required|integer|min:'.$cfg['min_topup_paise']]);
        $w = $r->user()->walletOrCreate();
        if ($w->balance_paise + $d['amount_paise'] > $cfg['max_balance_paise']) {
            throw ValidationException::withMessages(['amount_paise' => 'Wallet can hold up to ₹'.number_format($cfg['max_balance_paise'] / 100).'.']);
        }
        $order = $rzp->createOrder($d['amount_paise'], 'WAL-'.$w->id.'-'.now()->timestamp, ['wallet_id' => (string) $w->id]);
        $w->topups()->create(['user_id' => $r->user()->id, 'method' => 'razorpay', 'amount_paise' => $d['amount_paise'], 'gateway_order_id' => $order['id'], 'status' => 'created']);
        return response()->json(['payment' => [
            'key_id' => $rzp->keyId(), 'razorpay_order_id' => $order['id'], 'amount_paise' => $d['amount_paise'],
            'name' => config('dailytable.brand'), 'description' => 'Wallet top-up',
            'prefill' => ['name' => $r->user()->name, 'contact' => $r->user()->phone, 'email' => $r->user()->email],
        ]], 201);
    }
}

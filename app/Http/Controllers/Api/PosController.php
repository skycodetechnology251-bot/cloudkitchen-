<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Kitchen;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OrderService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    private function kitchen(Request $r, int $id): Kitchen
    {
        $u = $r->user();
        abort_unless($u->canUseKitchen($id), 403, 'You are not assigned to this kitchen.');
        return Kitchen::findOrFail($id);
    }

    /** Staff switch: verify a cashier PIN on a shared counter tablet. */
    public function pin(Request $r)
    {
        $d = $r->validate(['user_id' => 'required|exists:users,id', 'pin' => 'required|digits_between:4,6']);
        $u = User::findOrFail($d['user_id']);
        if (! $u->pos_pin || ! Hash::check($d['pin'], $u->pos_pin) || ! $u->hasPermission('pos.use')) {
            throw ValidationException::withMessages(['pin' => 'Wrong PIN.']);
        }
        return response()->json(['token' => $u->createToken('pos', ['staff', 'pos'], now()->addHours(12))->plainTextToken, 'name' => $u->name]);
    }

    public function menu(Request $r, int $kitchen)
    {
        $k = $this->kitchen($r, $kitchen);
        return response()->json(DailyMenu::with('dish:id,name,diet,category_id,image_path', 'dish.category:id,name')
            ->where('kitchen_id', $k->id)->whereDate('service_date', now(config('dailytable.timezone'))->toDateString())->get()
            ->map(fn ($m) => ['daily_menu_id' => $m->id, 'name' => $m->dish->name, 'diet' => $m->dish->diet, 'category' => $m->dish->category->name, 'price_paise' => $m->price_paise, 'available' => $m->available()]));
    }

    public function quote(Request $r, int $kitchen, PricingService $pricing)
    {
        $k = $this->kitchen($r, $kitchen);
        [$customer, $items, $d] = $this->parse($r);
        return response()->json($pricing->quote($customer, $k, $items, $d + ['channel' => 'pos']));
    }

    public function order(Request $r, int $kitchen, OrderService $orders)
    {
        $k = $this->kitchen($r, $kitchen);
        [$customer, $items, $d] = $this->parse($r);
        if (($d['discount_percent'] ?? 0) > 0 && ! $r->user()->hasPermission('pos.discount')) {
            throw ValidationException::withMessages(['discount' => 'Your role cannot give discounts.']);
        }
        if (($d['discount_percent'] ?? 0) > 10 && ! $r->user()->hasPermission('pos.void')) {
            throw ValidationException::withMessages(['discount' => 'Discounts above 10% need a manager PIN.']);
        }
        $res = $orders->place($customer, $k, $items, $d + ['channel' => 'pos', 'cashier_id' => $r->user()->id, 'pay' => $d['pay']]);
        AuditService::log('pos.sale', $res['order'], ['pay' => $d['pay']]);
        return response()->json(['order' => $res['order']->load('invoice'), 'receipt_url' => route('pos.receipt', $res['order'])], 201);
    }

    private function parse(Request $r): array
    {
        $d = $r->validate([
            'items' => 'required|array|min:1',
            'items.*.daily_menu_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'pay' => 'required|in:cash,upi_pos,card_pos,wallet',
            'customer_phone' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'offer_code' => 'nullable|string',
            'discount_percent' => 'nullable|integer|min:0|max:50',
        ]);
        $customer = ! empty($d['customer_phone']) ? User::where('phone', $d['customer_phone'])->first() : null;
        if ($d['pay'] === 'wallet' && ! $customer) {
            throw ValidationException::withMessages(['customer_phone' => 'Find the customer to pay from their wallet.']);
        }
        $items = [];
        foreach ($d['items'] as $i) {
            $items[(int) $i['daily_menu_id']] = ($items[(int) $i['daily_menu_id']] ?? 0) + (int) $i['quantity'];
        }
        return [$customer, $items, $d];
    }
}

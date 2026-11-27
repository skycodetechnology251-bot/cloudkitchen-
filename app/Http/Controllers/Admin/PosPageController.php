<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kitchen;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Counter POS: one per kitchen, open to every staff member for the kitchens they work in. */
class PosPageController extends Controller
{
    /** The POS button in the top bar: last kitchen used, the only kitchen, or a kitchen picker. */
    public function open(): View|RedirectResponse
    {
        $u = auth()->user();
        $kitchens = $u->accessibleKitchens();
        $last = session('pos_kitchen');
        if ($last && $kitchens->contains('id', $last)) {
            return redirect()->route('admin.pos', $last);
        }
        if ($kitchens->count() === 1) {
            return redirect()->route('admin.pos', $kitchens->first());
        }

        return view('admin.pos-pick', ['kitchens' => $kitchens]);
    }

    public function show(Kitchen $kitchen): View
    {
        $u = auth()->user();
        abort_unless($u->canUseKitchen($kitchen->id), 403, 'You are not assigned to this kitchen. Ask an admin to add you in Admin → Staff.');
        session(['pos_kitchen' => $kitchen->id]);
        // Short-lived token so the POS screen can call the API; old POS tokens of this user are cleared.
        $u->tokens()->where('name', 'web-pos')->where('created_at', '<', now()->subHours(12))->delete();
        $token = $u->createToken('web-pos', ['staff', 'pos'], now()->addHours(12))->plainTextToken;

        return view('admin.pos', ['kitchen' => $kitchen, 'token' => $token, 'kitchens' => $u->accessibleKitchens()]);
    }

    public function receipt(Order $order)
    {
        $u = auth()->user();
        abort_unless($u && ($u->hasPermission('pos.use') || $u->hasPermission('orders.view')) && $u->canUseKitchen($order->kitchen_id), 403);
        $order->load('items', 'invoice', 'cashier', 'payments');

        return view('invoices.receipt', compact('order'));
    }
}

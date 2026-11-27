<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;

class DashboardController extends Controller
{
    public function index()
    {
        $u = auth()->user();
        $ids = $u->branchIds();
        $today = now('Asia/Kolkata')->toDateString();
        $orders = $u->limitToBranches(Order::whereDate('service_date', $today));
        return view('admin.dashboard', [
            'kpis' => [
                'Orders today' => (clone $orders)->where('payment_status', 'paid')->count(),
                'Delivered' => (clone $orders)->where('status', 'delivered')->count(),
                'Sales today' => '₹'.number_format((clone $orders)->where('payment_status', 'paid')->sum('total_paise') / 100),
                'Riders on duty' => $u->limitStaffToBranches(User::where('on_duty', true))->count(),
            ],
            'zones' => Zone::withCount('kitchens')->when($ids !== null, fn ($q) => $q->whereHas('kitchens', fn ($k) => $k->whereIn('kitchens.id', $ids)))->get(),
            'kitchens' => $u->accessibleKitchens(),
            'recent' => $u->limitToBranches(Order::with('user:id,name', 'kitchen:id,name'))->latest()->limit(8)->get(),
            'scope' => $u->branchScopeLabel(),
        ]);
    }
}

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
        $today = now('Asia/Kolkata')->toDateString();
        $orders = Order::whereDate('service_date', $today);
        return view('admin.dashboard', [
            'kpis' => [
                'Orders today' => (clone $orders)->where('payment_status', 'paid')->count(),
                'Delivered' => (clone $orders)->where('status', 'delivered')->count(),
                'Sales today' => '₹'.number_format((clone $orders)->where('payment_status', 'paid')->sum('total_paise') / 100),
                'Riders on duty' => User::where('on_duty', true)->count(),
            ],
            'zones' => Zone::withCount('kitchens')->get(),
            'kitchens' => auth()->user()->role->slug === 'admin' ? Kitchen::all() : auth()->user()->kitchens,
            'recent' => Order::with('user:id,name')->latest()->limit(8)->get(),
        ]);
    }
}

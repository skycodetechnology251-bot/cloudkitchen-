<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kitchen;
use App\Models\User;

class KitchenBoardController extends Controller
{
    public function show(Kitchen $kitchen)
    {
        $u = auth()->user();
        abort_unless($u->canUseKitchen($kitchen->id), 403, 'You are not assigned to this kitchen. Ask an admin to add you in Admin → Staff.');
        $token = $u->createToken('web-kitchen', ['staff'], now()->addHours(12))->plainTextToken;
        $riders = User::where('on_duty', true)->whereHas('role', fn ($q) => $q->where('slug', 'rider'))->get(['id', 'name']);
        return view('admin.kitchen', compact('kitchen', 'token', 'riders'));
    }
}

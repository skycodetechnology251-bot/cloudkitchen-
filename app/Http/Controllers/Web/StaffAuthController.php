<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StaffAuthController extends Controller
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($d + ['is_active' => true], $r->boolean('remember')) || ! Auth::user()->isStaff()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'These details do not match a staff account.']);
        }
        $r->session()->regenerate();
        AuditService::log('staff.login');
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/staff/login');
    }
}

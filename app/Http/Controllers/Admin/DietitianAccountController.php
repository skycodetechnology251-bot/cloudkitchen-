<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin → Dietitian desk → Dietitian logins.
 * One simple form to create a dietitian's sign-in. They log in at /staff/login
 * with the work email and password and land straight on the Dietitian desk.
 */
class DietitianAccountController extends Controller
{
    private function role(): Role
    {
        return Role::where('slug', 'dietitian')->firstOrFail();
    }

    public function index()
    {
        $role = $this->role();
        $people = User::where('role_id', $role->id)->orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.dietitian.accounts', ['people' => $people, 'loginUrl' => url('/staff/login')]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:120|unique:users,email',
            'phone' => ['nullable', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'password' => 'required|string|min:10|confirmed',
        ], ['password.min' => 'Use at least 10 characters for the password.', 'phone.regex' => 'Enter a 10-digit Indian mobile number.']);

        $u = new User;
        $u->forceFill([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => ($data['phone'] ?? null) ?: null,
            'phone_verified_at' => ! empty($data['phone']) ? now() : null,
            'password' => $data['password'],
            'role_id' => $this->role()->id,
            'is_active' => true,
        ])->save();
        // A dietitian works for every branch's menu, so link all branches the admin can see.
        $u->kitchens()->sync(auth()->user()->accessibleKitchens()->pluck('id')->all());
        AuditService::log('staff.dietitian_created', $u, ['email' => $u->email]);

        return back()->with('ok', "Login created for {$u->name}. They sign in at ".url('/staff/login')." with {$u->email}.");
    }

    public function update(Request $r, User $user)
    {
        abort_unless((int) $user->role_id === $this->role()->id, 404);
        $data = $r->validate([
            'action' => ['required', Rule::in(['toggle', 'password'])],
            'password' => 'required_if:action,password|nullable|string|min:10',
        ]);
        if ($data['action'] === 'toggle') {
            $user->forceFill(['is_active' => ! $user->is_active])->save();
            if (! $user->is_active) {
                $user->tokens()->delete();
            }
            AuditService::log($user->is_active ? 'staff.enabled' : 'staff.disabled', $user);
            return back()->with('ok', $user->name.($user->is_active ? ' can sign in again.' : ' can no longer sign in.'));
        }
        $user->forceFill(['password' => $data['password']])->save();
        AuditService::log('staff.password_reset', $user);

        return back()->with('ok', "New password saved for {$user->name}.");
    }
}

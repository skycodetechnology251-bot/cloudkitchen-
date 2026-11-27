<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /** Health permissions can only belong to practitioner roles. */
    private const HEALTH_ONLY = ['health.view', 'health.plans'];
    private const PRACTITIONERS = ['dietitian', 'doctor'];

    public function index(Request $r)
    {
        $roles = Role::with('permissions')->withCount('users')->where('is_staff', true)->get();
        $current = $roles->firstWhere('id', (int) $r->input('role')) ?? $roles->first();
        return view('admin.roles', ['roles' => $roles, 'current' => $current, 'groups' => Permission::orderBy('group')->get()->groupBy('group')]);
    }

    public function update(Request $r, Role $role)
    {
        abort_if($role->slug === 'admin', 403, 'The admin role always has every permission.');
        $ids = collect($r->input('permissions', []))->map(fn ($v) => (int) $v);
        $perms = Permission::whereIn('id', $ids)->get();
        if (! in_array($role->slug, self::PRACTITIONERS, true)) {
            $perms = $perms->reject(fn ($p) => in_array($p->slug, self::HEALTH_ONLY, true));
        }
        $role->permissions()->sync($perms->pluck('id'));
        AuditService::log('role.permissions', $role, ['permissions' => $perms->pluck('slug')]);
        return redirect()->route('admin.roles.index', ['role' => $role->id])->with('status', $role->name.' updated.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Kitchen;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends CrudController
{
    protected string $model = User::class;
    protected string $title = 'Staff';
    protected string $route = 'admin.staff';
    protected array $columns = ['name' => 'Name', 'email' => 'Email', 'phone' => 'Mobile', 'role.name' => 'Role', 'is_active' => 'Active'];

    protected function query()
    {
        return User::with('role')->whereNotNull('role_id')->latest('id');
    }

    protected function fields(?Model $item): array
    {
        return [
            'name' => ['label' => 'Full name', 'rules' => 'required|string|max:80'],
            'email' => ['label' => 'Work email (web login)', 'rules' => 'nullable|email|unique:users,email'.($item ? ','.$item->id : '')],
            'phone' => ['label' => 'Mobile (app login)', 'rules' => ['nullable', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'.($item ? ','.$item->id : '')]],
            'role_id' => ['label' => 'Role', 'type' => 'select', 'options' => Role::where('is_staff', true)->pluck('name', 'id')->all(), 'rules' => 'required|exists:roles,id'],
            'kitchen_ids' => ['label' => 'Kitchens they work in', 'type' => 'multiselect', 'options' => Kitchen::pluck('name', 'id')->all(), 'value' => $item?->kitchens()->pluck('kitchens.id')->all() ?? [], 'rules' => 'nullable|array'],
            'password' => ['label' => $item ? 'New password (leave blank to keep)' : 'Password', 'type' => 'password', 'rules' => ($item ? 'nullable' : 'required_with:email|nullable').'|min:10'],
            'pos_pin' => ['label' => 'POS PIN (4–6 digits)', 'type' => 'password', 'rules' => 'nullable|digits_between:4,6'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $kitchens = $data['kitchen_ids'] ?? [];
        unset($data['kitchen_ids']);
        if (isset($data['pos_pin'])) {
            $data['pos_pin'] = Hash::make($r->input('pos_pin'));
        }
        if (! empty($data['phone']) && ! $item->phone_verified_at) {
            $data['phone_verified_at'] = now();
        }
        $roleChanged = $item->exists && (int) $item->role_id !== (int) $data['role_id'];
        $item->forceFill($data)->save();
        $item->kitchens()->sync($kitchens);
        if ($roleChanged) {
            $item->tokens()->delete();
            AuditService::log('staff.role_changed', $item, ['role_id' => $data['role_id']]);
        }
    }
}

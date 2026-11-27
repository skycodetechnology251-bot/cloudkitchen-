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

    /** The Salary column shows only to people who may see salaries (Admin, Finance). */
    public function index(Request $r)
    {
        if (auth()->user()->hasPermission('staff.salary')) {
            $this->columns = ['name' => 'Name', 'phone' => 'Mobile', 'role.name' => 'Role', 'salary_label' => 'Salary', 'is_active' => 'Active'];
        }

        return parent::index($r);
    }

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
        ] + $this->salaryFields($item);
    }

    /** Own salary for this person; blank fields use the salary set on their role (Admin → Roles). */
    private function salaryFields(?Model $item): array
    {
        if (! auth()->user()->hasPermission('staff.salary')) {
            return [];
        }
        $role = $item?->role;
        $hint = $role ? 'Leave blank to use the '.$role->name.' role salary: '.Role::salaryText($role->salary_type, $role->salary_paise, $role->per_delivery_paise).'.' : 'Leave blank to use the salary set on the role.';

        return [
            'salary_type' => ['label' => 'Salary type', 'type' => 'select', 'options' => ['' => 'Same as role'] + Role::SALARY_TYPES, 'rules' => 'nullable|in:'.implode(',', array_keys(Role::SALARY_TYPES)), 'help' => $hint],
            'salary_paise' => ['label' => 'Salary amount (₹) — per month, day, hour or delivery, by type', 'type' => 'money', 'rules' => 'nullable|numeric|min:0|max:1000000'],
            'per_delivery_paise' => ['label' => 'Extra per delivery (₹, riders)', 'type' => 'money', 'rules' => 'nullable|numeric|min:0|max:10000'],
            'joined_on' => ['label' => 'Joined on', 'type' => 'date', 'rules' => 'nullable|date'],
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
        if (array_key_exists('salary_type', $data) && $data['salary_type'] === '') {
            $data['salary_type'] = null;
        }
        $salaryChanged = $item->exists && collect(['salary_type', 'salary_paise', 'per_delivery_paise'])->contains(fn ($k) => array_key_exists($k, $data) && $item->{$k} != $data[$k]);
        $roleChanged = $item->exists && (int) $item->role_id !== (int) $data['role_id'];
        $item->forceFill($data)->save();
        $item->kitchens()->sync($kitchens);
        if ($salaryChanged) {
            AuditService::log('staff.salary_changed', $item, ['salary_type' => $item->salary_type, 'salary_paise' => $item->salary_paise, 'per_delivery_paise' => $item->per_delivery_paise]);
        }
        if ($roleChanged) {
            $item->tokens()->delete();
            AuditService::log('staff.role_changed', $item, ['role_id' => $data['role_id']]);
        }
    }
}

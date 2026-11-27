<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** Safe to run again: roles and permissions are upserted, nothing is deleted. */
class AccessSeeder extends Seeder
{
    public const PERMISSIONS = [
        ['pos', 'pos.use', 'Take counter orders', false],
        ['pos', 'pos.discount', 'Give discounts up to 10%', false],
        ['pos', 'pos.void', 'Void, refund or discount above 10% at counter', true],
        ['orders', 'orders.view', 'View orders', false],
        ['orders', 'orders.refund', 'Issue refunds', true],
        ['menu & food', 'menu.edit', 'Edit dishes & recipes', false],
        ['menu & food', 'menu.publish', 'Publish daily menu', false],
        ['menu & food', 'menu.nutrition', 'Approve nutrition & suitability', false],
        ['kitchen', 'kitchen.board', 'Production board', false],
        ['kitchen', 'kitchen.pause', 'Pause dishes & change portions', false],
        ['kitchen', 'expenses.edit', 'Record purchases & expenses', false],
        ['delivery', 'delivery.assign', 'Assign riders', false],
        ['customers & health', 'cust.contact', 'View customer contact details', false],
        ['customers & health', 'health.view', 'View consented health profiles', true],
        ['customers & health', 'health.plans', 'Create & send diet plans', true],
        ['branches', 'branches.all', 'See & manage every branch (head office)', true],
        ['zones', 'zones.edit', 'Create & edit branches and zones', false],
        ['zones', 'zones.golive', 'Approve zone go-live', false],
        ['marketing', 'mkt.banners', 'Manage banners, popups & offers', false],
        ['staff', 'staff.manage', 'Invite & manage staff', true],
        ['staff', 'staff.roles', 'Edit roles & permissions', true],
        ['staff', 'staff.salary', 'Set salaries & run payroll', true],
        ['finance', 'fin.reports', 'View reports', false],
        ['finance', 'fin.export', 'Export data', true],
        ['settings', 'settings.manage', 'Manage integrations & keys', true],
    ];

    public const ROLES = [
        'admin' => ['Admin', 'Full control of the platform.', ['*']],
        'ops' => ['Operations manager', 'Runs daily service, approves go-live, handles escalations.', ['pos.use', 'pos.discount', 'pos.void', 'orders.view', 'orders.refund', 'menu.publish', 'kitchen.board', 'kitchen.pause', 'delivery.assign', 'cust.contact', 'zones.edit', 'zones.golive', 'mkt.banners', 'staff.manage', 'fin.reports', 'expenses.edit']],
        'kitchen_manager' => ['Kitchen manager', 'Production, stock, expenses and dish availability for their branch.', ['pos.use', 'pos.discount', 'orders.view', 'menu.edit', 'menu.publish', 'kitchen.board', 'kitchen.pause', 'delivery.assign', 'expenses.edit']],
        'kitchen_staff' => ['Kitchen staff', 'Cooks and packers: production board and counter POS.', ['kitchen.board', 'pos.use']],
        'cashier' => ['POS cashier', 'Counter and phone orders, invoices.', ['pos.use', 'pos.discount', 'orders.view']],
        'support' => ['Support agent', 'Customer tickets and approved refunds.', ['pos.use', 'orders.view', 'orders.refund', 'cust.contact']],
        'finance' => ['Finance', 'Payments, refunds, payroll and reports.', ['pos.use', 'orders.view', 'fin.reports', 'fin.export', 'staff.salary']],
        'dietitian' => ['Dietitian', 'Recipe review and diet plans for consented customers.', ['pos.use', 'menu.edit', 'menu.nutrition', 'health.view', 'health.plans', 'orders.view']],
        'doctor' => ['Doctor', 'Views assigned consented patients and approves plans.', ['pos.use', 'health.view', 'health.plans']],
        'rider' => ['Delivery partner', 'Assigned deliveries only, via the rider app.', []],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as [$group, $slug, $name, $sensitive]) {
            Permission::updateOrCreate(['slug' => $slug], compact('group', 'name', 'sensitive'));
        }
        foreach (self::ROLES as $slug => [$name, $desc, $perms]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $desc, 'is_staff' => true]);
            // Admin always gets every permission (also new ones added by an update).
            // Other roles get their defaults only once, so changes made in Admin → Roles are kept.
            if ($perms === ['*']) {
                $role->permissions()->sync(Permission::pluck('id'));
            } elseif ($role->wasRecentlyCreated) {
                $role->permissions()->sync(Permission::whereIn('slug', $perms)->pluck('id'));
            }
        }
        // The first admin account is created by the installer (browser /install or php artisan dailytable:install).
    }
}

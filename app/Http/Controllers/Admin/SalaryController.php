<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryStop;
use App\Models\Expense;
use App\Models\Kitchen;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin → Salaries: a default salary for each role, and a monthly payroll.
 * Each payment is also recorded as a "salary" expense, so it shows in Reports (income vs expenses).
 */
class SalaryController extends Controller
{
    private function month(Request $r): Carbon
    {
        $m = (string) $r->input('month', now(config('dailytable.timezone'))->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m), 404);

        return Carbon::createFromFormat('Y-m-d', $m.'-01', config('dailytable.timezone'))->startOfMonth();
    }

    /** Deliveries each rider completed in the month (for per-delivery pay). */
    private function deliveries(Carbon $month): array
    {
        return DeliveryStop::query()->join('delivery_batches', 'delivery_batches.id', '=', 'delivery_stops.delivery_batch_id')
            ->where('delivery_stops.status', 'delivered')
            ->whereBetween('delivery_stops.delivered_at', [$month->copy()->utc(), $month->copy()->endOfMonth()->utc()])
            ->groupBy('delivery_batches.rider_id')->select('delivery_batches.rider_id', DB::raw('count(*) as n'))
            ->pluck('n', 'rider_id')->map(fn ($n) => (int) $n)->all();
    }

    public function index(Request $r): View
    {
        $month = $this->month($r);
        $key = $month->format('Y-m');
        $deliveries = $this->deliveries($month);
        $paid = SalaryPayment::where('month', $key)->get()->keyBy('user_id');
        $staff = User::with('role')->whereNotNull('role_id')->whereHas('role', fn ($q) => $q->where('is_staff', true))
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $paid->keys()))->orderBy('name')->get();

        $rows = $staff->map(function (User $u) use ($deliveries, $paid) {
            $p = $u->salaryPlan();
            $d = $deliveries[$u->id] ?? 0;

            return ['user' => $u, 'plan' => $p, 'deliveries' => $d, 'payment' => $paid->get($u->id),
                // Suggested pay before bonus / deductions. Daily and hourly staff: entered on the row.
                'suggested' => match ($p['type']) {
                    'monthly' => $p['paise'] + $d * $p['per_delivery_paise'],
                    'per_delivery' => $d * $p['paise'],
                    default => $d * $p['per_delivery_paise'],
                }];
        });

        return view('admin.salaries', [
            'month' => $month, 'rows' => $rows,
            'roles' => Role::where('is_staff', true)->withCount(['users' => fn ($q) => $q->where('is_active', true)])->orderBy('id')->get(),
            'types' => Role::SALARY_TYPES,
            'totalPaid' => $paid->sum('net_paise'),
            'tab' => $r->input('tab', 'payroll'),
        ]);
    }

    public function updateRole(Request $r, Role $role): RedirectResponse
    {
        $d = $r->validate([
            'salary_type' => ['required', Rule::in(array_keys(Role::SALARY_TYPES))],
            'salary' => 'nullable|numeric|min:0|max:1000000',
            'per_delivery' => 'nullable|numeric|min:0|max:10000',
        ]);
        $role->forceFill([
            'salary_type' => $d['salary_type'],
            'salary_paise' => (int) round(((float) ($d['salary'] ?? 0)) * 100),
            'per_delivery_paise' => (int) round(((float) ($d['per_delivery'] ?? 0)) * 100),
        ])->save();
        AuditService::log('role.salary', $role, $role->only(['salary_type', 'salary_paise', 'per_delivery_paise']));

        return redirect()->route('admin.salaries', ['tab' => 'roles', 'month' => $r->input('month')])
            ->with('status', $role->name.' salary saved: '.Role::salaryText($role->salary_type, $role->salary_paise, $role->per_delivery_paise).'. Staff with their own amount keep it.');
    }

    public function pay(Request $r, User $user): RedirectResponse
    {
        $month = $this->month($r);
        $key = $month->format('Y-m');
        $plan = $user->salaryPlan();
        $needsUnits = in_array($plan['type'], ['daily', 'hourly'], true);
        $d = $r->validate([
            'units' => [$needsUnits ? 'required' : 'nullable', 'numeric', 'min:0', 'max:744'],
            'bonus' => 'nullable|numeric|min:0|max:1000000',
            'deduction' => 'nullable|numeric|min:0|max:1000000',
            'method' => 'required|in:bank,upi,cash',
            'reference' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:190',
        ], ['units.required' => $plan['type'] === 'daily' ? 'Enter the days worked.' : 'Enter the hours worked.']);
        abort_if(SalaryPayment::where('user_id', $user->id)->where('month', $key)->exists(), 422, $user->name.' is already paid for '.$month->format('F Y').'.');

        $deliveries = $this->deliveries($month)[$user->id] ?? 0;
        $units = $needsUnits ? (float) $d['units'] : null;
        $base = match ($plan['type']) {
            'monthly' => $plan['paise'],
            'daily', 'hourly' => (int) round($plan['paise'] * $units),
            'per_delivery' => $plan['paise'] * $deliveries,
        };
        $deliveryPay = $plan['type'] === 'per_delivery' ? 0 : $deliveries * $plan['per_delivery_paise'];
        $bonus = (int) round(((float) ($d['bonus'] ?? 0)) * 100);
        $deduction = (int) round(((float) ($d['deduction'] ?? 0)) * 100);
        $net = $base + $deliveryPay + $bonus - $deduction;
        if ($net <= 0) {
            return back()->withErrors(['pay_'.$user->id => 'Pay for '.$user->name.' comes to ₹0. Set a salary for the role or this person first.'])->withInput();
        }
        $kitchenId = $user->kitchens()->value('kitchens.id') ?? Kitchen::orderBy('id')->value('id');
        if (! $kitchenId) {
            return back()->withErrors(['pay_'.$user->id => 'Add a kitchen first (Admin → Kitchens): salaries are recorded as kitchen expenses.']);
        }

        DB::transaction(function () use ($user, $key, $month, $plan, $units, $base, $deliveries, $deliveryPay, $bonus, $deduction, $net, $d, $kitchenId) {
            $expense = Expense::create([
                'kitchen_id' => $kitchenId, 'category' => 'salary',
                'description' => 'Salary · '.$user->name.' · '.$month->format('M Y'),
                'supplier' => $user->name, 'amount_paise' => $net, 'paid_paise' => $net,
                'spent_on' => now(config('dailytable.timezone'))->toDateString(), 'recorded_by' => auth()->id(),
            ]);
            $payment = SalaryPayment::create([
                'user_id' => $user->id, 'month' => $key, 'role_name' => $user->role?->name ?? '', 'salary_type' => $plan['type'],
                'rate_paise' => $plan['paise'], 'units' => $units, 'base_paise' => $base, 'deliveries' => $deliveries,
                'delivery_pay_paise' => $deliveryPay, 'bonus_paise' => $bonus, 'deduction_paise' => $deduction, 'net_paise' => $net,
                'method' => $d['method'], 'reference' => $d['reference'] ?? null, 'note' => $d['note'] ?? null,
                'expense_id' => $expense->id, 'paid_by' => auth()->id(), 'paid_at' => now(),
            ]);
            AuditService::log('salary.paid', $payment, ['user' => $user->id, 'month' => $key, 'net_paise' => $net]);
        });

        return redirect()->route('admin.salaries', ['month' => $key])->with('status', $user->name.' paid ₹'.number_format($net / 100, 2).' for '.$month->format('F Y').'.');
    }

    /** Undo a payment recorded by mistake (also removes its expense). */
    public function destroy(SalaryPayment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment) {
            AuditService::log('salary.undone', $payment, ['user' => $payment->user_id, 'month' => $payment->month, 'net_paise' => $payment->net_paise]);
            $expense = $payment->expense;
            $payment->delete();
            $expense?->delete();
        });

        return redirect()->route('admin.salaries', ['month' => $payment->month])->with('status', 'Payment removed. You can record it again.');
    }

    public function payslip(SalaryPayment $payment): View
    {
        $payment->load('user.role', 'user.kitchens', 'payer');

        return view('admin.payslip', ['p' => $payment]);
    }
}

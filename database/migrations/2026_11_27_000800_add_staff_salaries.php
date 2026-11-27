<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Version 1.4: salaries.
 * - Each role has a default salary (monthly, daily, hourly or per delivery, plus an optional per-delivery bonus).
 * - A staff member can have their own amount; blank means "use the role's".
 * - salary_payments records each month's pay (one per person per month) and links to the expense it creates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'salary_type')) {
                $table->string('salary_type', 12)->default('monthly')->after('is_staff');   // monthly | daily | hourly | per_delivery
                $table->unsignedInteger('salary_paise')->default(0)->after('salary_type');   // per month / day / hour / delivery
                $table->unsignedInteger('per_delivery_paise')->default(0)->after('salary_paise'); // extra per delivery (riders)
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'salary_type')) {
                $table->string('salary_type', 12)->nullable()->after('role_id');
                $table->unsignedInteger('salary_paise')->nullable()->after('salary_type');
                $table->unsignedInteger('per_delivery_paise')->nullable()->after('salary_paise');
                $table->date('joined_on')->nullable()->after('per_delivery_paise');
            }
        });
        if (! Schema::hasTable('salary_payments')) {
            Schema::create('salary_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->char('month', 7);                                   // 2026-11
                $table->string('role_name');
                $table->string('salary_type', 12);
                $table->unsignedInteger('rate_paise');
                $table->decimal('units', 7, 2)->nullable();                 // days or hours worked
                $table->unsignedInteger('base_paise');
                $table->unsignedInteger('deliveries')->default(0);
                $table->unsignedInteger('delivery_pay_paise')->default(0);
                $table->unsignedInteger('bonus_paise')->default(0);
                $table->unsignedInteger('deduction_paise')->default(0);
                $table->unsignedInteger('net_paise');
                $table->string('method', 12)->default('bank');              // bank | upi | cash
                $table->string('reference')->nullable();
                $table->string('note')->nullable();
                $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at');
                $table->timestamps();
                $table->unique(['user_id', 'month']);
            });
        }

        // New permission, for admin and finance (the seeder adds it on fresh installs too).
        if (Schema::hasTable('permissions') && DB::table('permissions')->exists()) {
            $id = DB::table('permissions')->where('slug', 'staff.salary')->value('id')
                ?? DB::table('permissions')->insertGetId(['slug' => 'staff.salary', 'name' => 'Set salaries & run payroll', 'group' => 'staff', 'sensitive' => true, 'created_at' => now(), 'updated_at' => now()]);
            foreach (DB::table('roles')->whereIn('slug', ['admin', 'finance'])->pluck('id') as $role) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['salary_type', 'salary_paise', 'per_delivery_paise', 'joined_on']));
        Schema::table('roles', fn (Blueprint $t) => $t->dropColumn(['salary_type', 'salary_paise', 'per_delivery_paise']));
    }
};

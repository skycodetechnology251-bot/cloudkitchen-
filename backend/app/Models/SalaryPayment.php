<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One month's pay for one staff member (base + delivery pay + bonus − deductions). */
class SalaryPayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'units' => 'decimal:2'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function payer() { return $this->belongsTo(User::class, 'paid_by'); }
    public function expense() { return $this->belongsTo(Expense::class); }
}

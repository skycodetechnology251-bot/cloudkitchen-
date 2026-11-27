<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = ['id', 'user_id', 'balance_paise'];

    protected function casts(): array
    {
        return ['pay_first' => 'boolean', 'auto_topup' => 'boolean', 'last_low_alert_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(WalletTransaction::class)->latest('id'); }
    public function topups() { return $this->morphMany(Payment::class, 'payable'); }

    public function isLow(): bool
    {
        return $this->balance_paise < $this->low_balance_paise;
    }
}

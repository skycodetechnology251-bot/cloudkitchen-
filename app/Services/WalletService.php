<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Messaging\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Ledger-based wallet. Every change locks the wallet row and writes a transaction. */
class WalletService
{
    public function __construct(private NotificationService $notify) {}

    public function credit(User $user, int $paise, string $type, ?Model $ref = null, ?string $note = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $paise, $type, $ref, $note) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first() ?? $user->walletOrCreate();
            $wallet->balance_paise += $paise;
            if ($wallet->balance_paise > config('dailytable.wallet.max_balance_paise')) {
                throw new RuntimeException('Wallet balance limit reached.');
            }
            $wallet->save();
            return $this->entry($wallet, $type, $paise, $ref, $note);
        });
    }

    /** Debit inside an existing transaction (orders). Throws if insufficient. */
    public function debit(User $user, int $paise, string $type, ?Model $ref = null, ?string $note = null): WalletTransaction
    {
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
        if (! $wallet || $wallet->balance_paise < $paise) {
            throw new RuntimeException('Not enough wallet balance.');
        }
        $wallet->balance_paise -= $paise;
        $wallet->save();
        $tx = $this->entry($wallet, $type, -$paise, $ref, $note);
        DB::afterCommit(fn () => $this->maybeLowBalanceAlert($wallet->fresh()));
        return $tx;
    }

    public function maybeLowBalanceAlert(Wallet $wallet): void
    {
        if (! $wallet->isLow() || ($wallet->last_low_alert_at && $wallet->last_low_alert_at->isToday())) {
            return;
        }
        $wallet->update(['last_low_alert_at' => now()]);
        $rupees = number_format($wallet->balance_paise / 100, 0);
        $this->notify->notify($wallet->user, 'wallet', 'Wallet running low', "Your Daily Table wallet has ₹{$rupees}. Top up to keep your daily lunch going.", [
            'data' => ['screen' => 'Wallet'], 'wa_template' => 'wallet_low', 'wa_params' => [$wallet->user->name, $rupees],
        ]);
    }

    private function entry(Wallet $wallet, string $type, int $amount, ?Model $ref, ?string $note): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount_paise' => $amount,
            'balance_after_paise' => $wallet->balance_paise,
            'reference_type' => $ref?->getMorphClass(),
            'reference_id' => $ref?->getKey(),
            'note' => $note,
        ]);
    }
}

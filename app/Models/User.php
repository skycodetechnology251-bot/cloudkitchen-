<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** New accounts are active (matches the column default before the row is re-read). */
    protected $attributes = ['is_active' => true];

    use HasApiTokens, Notifiable, SoftDeletes;

    protected $guarded = ['id', 'role_id', 'password', 'pos_pin'];

    protected $hidden = ['password', 'pos_pin', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'joined_on' => 'date',
            'last_seen_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'on_duty' => 'boolean',
        ];
    }

    public function role() { return $this->belongsTo(Role::class); }
    public function healthProfile() { return $this->hasOne(HealthProfile::class); }
    public function addresses() { return $this->hasMany(Address::class)->whereNull('archived_at'); }
    public function wallet() { return $this->hasOne(Wallet::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function kitchens() { return $this->belongsToMany(Kitchen::class); }
    public function deviceTokens() { return $this->hasMany(DeviceToken::class); }
    public function points() { return $this->hasMany(PointTransaction::class); }

    public function isStaff(): bool
    {
        return (bool) $this->role?->is_staff;
    }

    public function hasPermission(string $slug): bool
    {
        if (! $this->role) {
            return false;
        }
        if ($this->role->slug === 'admin') {
            return true;
        }
        return $this->role->permissions->contains('slug', $slug);
    }

    /**
     * Salary that applies to this person: their own amount if set, otherwise their role's.
     * @return array{type: string, paise: int, per_delivery_paise: int, own: bool}
     */
    public function salaryPlan(): array
    {
        $own = $this->salary_paise !== null || $this->per_delivery_paise !== null || $this->salary_type !== null;

        return [
            'type' => $this->salary_type ?? $this->role?->salary_type ?? 'monthly',
            'paise' => (int) ($this->salary_paise ?? $this->role?->salary_paise ?? 0),
            'per_delivery_paise' => (int) ($this->per_delivery_paise ?? $this->role?->per_delivery_paise ?? 0),
            'own' => $own,
        ];
    }

    public function getSalaryLabelAttribute(): string
    {
        $p = $this->salaryPlan();

        return Role::salaryText($p['type'], $p['paise'], $p['per_delivery_paise']).($p['own'] ? '' : ' (role)');
    }

    public function salaryPayments() { return $this->hasMany(SalaryPayment::class); }

    /** Admin and operations managers work across every kitchen; other staff only in kitchens assigned to them. */
    public function seesAllKitchens(): bool
    {
        return in_array($this->role?->slug, ['admin', 'ops'], true);
    }

    /** Kitchens this staff member may open the POS and kitchen board for. */
    public function accessibleKitchens(): \Illuminate\Support\Collection
    {
        return $this->seesAllKitchens() ? Kitchen::orderBy('name')->get() : $this->kitchens()->orderBy('name')->get();
    }

    public function canUseKitchen(int $kitchenId): bool
    {
        return $this->seesAllKitchens() || $this->kitchens()->whereKey($kitchenId)->exists();
    }

    public function pointsBalance(): int
    {
        return (int) $this->points()->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->sum('points');
    }

    public function walletOrCreate(): Wallet
    {
        if (! $this->wallet) {
            // firstOrCreate + setRelation: safe to call several times in one request.
            $this->setRelation('wallet', $this->wallet()->firstOrCreate([], ['balance_paise' => 0]));
        }

        return $this->wallet;
    }
}

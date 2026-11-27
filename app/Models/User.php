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
            'coach_consent_at' => 'datetime',
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
    public function wellnessDays() { return $this->hasMany(WellnessDay::class); }
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
    /** Head office: sees and manages every branch. Admin always; other roles if given "branches.all" in Roles. */
    public function seesAllKitchens(): bool
    {
        return $this->hasPermission('branches.all');
    }

    private ?array $branchIdCache = null;
    private bool $branchIdLoaded = false;

    /** Branch ids this person may see; null means every branch. */
    public function branchIds(): ?array
    {
        if (! $this->branchIdLoaded) {
            $this->branchIdCache = $this->seesAllKitchens() ? null : $this->kitchens()->pluck('kitchens.id')->map(fn ($i) => (int) $i)->all();
            $this->branchIdLoaded = true;
        }
        return $this->branchIdCache;
    }

    public function canSeeBranch(?int $kitchenId): bool
    {
        $ids = $this->branchIds();
        return $ids === null || ($kitchenId !== null && in_array($kitchenId, $ids, true));
    }

    /** Limit a query on a table with a kitchen_id column to this person's branches. */
    public function limitToBranches($query, string $column = 'kitchen_id')
    {
        $ids = $this->branchIds();
        return $ids === null ? $query : $query->whereIn($column, $ids);
    }

    /** Limit a users query to staff who work in at least one of this person's branches. */
    public function limitStaffToBranches($query)
    {
        $ids = $this->branchIds();
        return $ids === null ? $query : $query->whereHas('kitchens', fn ($k) => $k->whereIn('kitchens.id', $ids));
    }

    public function canSeeStaff(User $other): bool
    {
        $ids = $this->branchIds();
        return $ids === null || $other->kitchens()->whereIn('kitchens.id', $ids)->exists();
    }

    /** "All branches" or the names of this person's branches, for page headers. */
    public function branchScopeLabel(): string
    {
        return $this->branchIds() === null ? 'All branches' : ($this->accessibleKitchens()->pluck('name')->join(', ') ?: 'No branch assigned yet');
    }

    /** Kitchens this staff member may open the POS and kitchen board for. */
    public function accessibleKitchens(): \Illuminate\Support\Collection
    {
        return $this->branchIds() === null ? Kitchen::orderBy('name')->get() : Kitchen::whereIn('id', $this->branchIds())->orderBy('name')->get();
    }

    public function canUseKitchen(int $kitchenId): bool
    {
        return $this->canSeeBranch($kitchenId);
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

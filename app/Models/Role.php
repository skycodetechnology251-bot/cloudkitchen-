<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean'];
    }

    public const SALARY_TYPES = ['monthly' => 'Monthly salary', 'daily' => 'Per day worked', 'hourly' => 'Per hour worked', 'per_delivery' => 'Per delivery'];

    /** "₹18,000 / month", "₹600 / day + ₹15 per delivery", or "Not set". */
    public static function salaryText(?string $type, ?int $paise, ?int $perDelivery = 0): string
    {
        if (! $paise && ! $perDelivery) {
            return 'Not set';
        }
        $unit = ['monthly' => ' / month', 'daily' => ' / day', 'hourly' => ' / hour', 'per_delivery' => ' / delivery'][$type] ?? '';
        $t = $paise ? '₹'.number_format($paise / 100).$unit : '';
        if ($perDelivery && $type !== 'per_delivery') {
            $t .= ($t ? ' + ' : '').'₹'.number_format($perDelivery / 100).' per delivery';
        }

        return $t;
    }

    public function permissions() { return $this->belongsToMany(Permission::class); }
    public function users() { return $this->hasMany(User::class); }
}

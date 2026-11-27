<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthProfile extends Model
{
    protected $guarded = ['id', 'user_id'];

    protected $hidden = ['conditions'];

    protected function casts(): array
    {
        return [
            'allergies' => 'array',
            'conditions' => 'encrypted:array',
            'conditions_consent_at' => 'datetime',
            'height_cm' => 'float',
            'weight_kg' => 'float',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function bmi(): ?float
    {
        if (! $this->height_cm || ! $this->weight_kg) {
            return null;
        }
        return round($this->weight_kg / (($this->height_cm / 100) ** 2), 1);
    }
}

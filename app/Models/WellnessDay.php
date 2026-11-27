<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WellnessDay extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => 'date', 'workouts' => 'array'];
    }

    public function user() { return $this->belongsTo(User::class); }
}

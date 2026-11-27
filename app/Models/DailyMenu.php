<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMenu extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['service_date' => 'date'];
    }

    public function dish() { return $this->belongsTo(Dish::class); }
    public function kitchen() { return $this->belongsTo(Kitchen::class); }

    public function available(): int
    {
        return max(0, $this->capacity - $this->reserved - $this->sold);
    }
}

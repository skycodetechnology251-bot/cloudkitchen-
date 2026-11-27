<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['spent_on' => 'date', 'quantity' => 'decimal:2'];
    }

    public function kitchen() { return $this->belongsTo(Kitchen::class); }
}

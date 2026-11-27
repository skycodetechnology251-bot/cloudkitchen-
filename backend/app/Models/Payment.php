<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['raw' => 'array'];
    }

    public function payable() { return $this->morphTo(); }
}

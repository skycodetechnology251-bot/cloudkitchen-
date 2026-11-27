<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sensitive' => 'boolean'];
    }

    public function roles() { return $this->belongsToMany(Role::class); }
}

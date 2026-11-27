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

    public function permissions() { return $this->belongsToMany(Permission::class); }
    public function users() { return $this->hasMany(User::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kitchen extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean', 'lat' => 'float', 'lng' => 'float'];
    }

    public function zones() { return $this->belongsToMany(Zone::class); }
    public function staff() { return $this->belongsToMany(User::class); }
    public function dailyMenus() { return $this->hasMany(DailyMenu::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Dish extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'balance' => 'array',
            'ingredients' => 'array',
            'allergens' => 'array',
            'suitable_for' => 'array',
            'reviewed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function category() { return $this->belongsTo(Category::class); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }
}

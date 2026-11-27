<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Admin-editable integration settings (Razorpay, SMS, WhatsApp, OpenAI, GST...).
 * Secrets are stored encrypted with APP_KEY; .env values are used as fallback.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['encrypted' => 'boolean'];
    }

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::all()->map(fn ($s) => [
            'k' => $s->group.'.'.$s->key,
            'v' => $s->encrypted && $s->value ? Crypt::decryptString($s->value) : $s->value,
        ])->pluck('v', 'k')->all());

        $value = $all[$group.'.'.$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }

    public static function put(string $group, string $key, ?string $value, bool $encrypted = false): void
    {
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $encrypted && $value !== null ? Crypt::encryptString($value) : $value, 'encrypted' => $encrypted]
        );
        Cache::forget('settings.all');
    }
}

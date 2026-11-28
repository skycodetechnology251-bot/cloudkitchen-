<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * The four meals: breakfast, lunch, break time (evening snacks) and dinner. Admin can switch each on or off
 * and set its order cut-off and delivery windows (Admin → Daily menu → Meal times).
 */
class Meals
{
    public const DEFAULTS = [
        'breakfast' => ['label' => 'Breakfast', 'icon' => '🌅', 'color' => '#F4B400', 'enabled' => true, 'cutoff' => '06:30', 'slots' => ['07:30-08:30', '08:30-09:30']],
        'lunch' => ['label' => 'Lunch', 'icon' => '🍛', 'color' => '#1F8A4C', 'enabled' => true, 'cutoff' => '10:45', 'slots' => ['12:00-13:00', '13:00-14:00']],
        'snacks' => ['label' => 'Break time', 'icon' => '☕', 'color' => '#F2711C', 'enabled' => true, 'cutoff' => '14:30', 'slots' => ['16:00-17:00', '17:00-18:00']],
        'dinner' => ['label' => 'Dinner', 'icon' => '🌙', 'color' => '#5B30C0', 'enabled' => true, 'cutoff' => '17:30', 'slots' => ['19:30-20:30', '20:30-21:30']],
    ];

    public static function keys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    /** All meals with admin changes applied. */
    public static function all(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $k => $d) {
            try {
                $saved = json_decode((string) Setting::get('meals', $k, ''), true) ?: [];
            } catch (Throwable) {
                $saved = [];
            }
            $m = array_merge($d, array_intersect_key($saved, array_flip(['enabled', 'cutoff', 'slots', 'label'])));
            if ($k === 'lunch') {
                $m['enabled'] = true; // lunch is the core service
            }
            $m['key'] = $k;
            $out[$k] = $m;
        }
        return $out;
    }

    public static function enabled(): array
    {
        return array_filter(self::all(), fn ($m) => $m['enabled']);
    }

    public static function get(string $key): array
    {
        return self::all()[$key] ?? self::all()['lunch'];
    }

    public static function label(string $key): string
    {
        return self::get($key)['label'];
    }

    public static function valid(?string $key): string
    {
        return in_array($key, self::keys(), true) ? $key : 'lunch';
    }

    public static function save(string $key, array $data): void
    {
        $slots = array_values(array_filter(array_map('trim', (array) ($data['slots'] ?? [])), fn ($s) => preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $s)));
        Setting::put('meals', $key, json_encode([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'cutoff' => preg_match('/^\d{2}:\d{2}$/', $data['cutoff'] ?? '') ? $data['cutoff'] : self::DEFAULTS[$key]['cutoff'],
            'slots' => $slots ?: self::DEFAULTS[$key]['slots'],
        ]));
    }
}

<?php

namespace App\Support;

/** Accent colour of each admin section: page headers, tables, buttons and fields follow it (see dt.css .acc-*). */
class Accent
{
    private const MAP = [
        'admin.dashboard' => 'teal', 'admin.orders' => 'blue', 'admin.invoice' => 'blue', 'admin.menus' => 'amber',
        'admin.dishes' => 'coral', 'admin.zones' => 'pink', 'admin.branches' => 'green', 'admin.banners' => 'purple',
        'admin.offers' => 'pink', 'admin.expenses' => 'amber', 'admin.reports' => 'sky', 'admin.salaries' => 'green',
        'admin.staff' => 'indigo', 'admin.roles' => 'purple', 'admin.settings' => 'sky', 'admin.system' => 'slate',
        'admin.kitchen' => 'coral', 'admin.pos' => 'teal',
    ];

    public static function current(): string
    {
        $name = (string) optional(request()->route())->getName();
        foreach (self::MAP as $prefix => $tone) {
            if ($name === $prefix || str_starts_with($name, $prefix.'.')) {
                return $tone;
            }
        }
        return 'teal';
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Safe to run on every install and update: adds roles and permissions, never removes data.
        // Demo data is separate (installer option, or: php artisan db:seed --class=DemoSeeder).
        $this->call(AccessSeeder::class);
    }
}

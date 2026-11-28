<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Four meals: breakfast, lunch, snacks ("Break time") and dinner. The meal columns become short strings
 * (they were enums without "snacks"), and dishes say which meals they suit.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['daily_menus', 'orders', 'meal_responses'] as $table) {
            if (Schema::hasColumn($table, 'meal')) {
                Schema::table($table, fn (Blueprint $t) => $t->string('meal', 20)->default('lunch')->change());
            }
        }
        if (! Schema::hasColumn('dishes', 'meal_types')) {
            Schema::table('dishes', fn (Blueprint $t) => $t->json('meal_types')->nullable()->after('diet'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dishes', 'meal_types')) {
            Schema::table('dishes', fn (Blueprint $t) => $t->dropColumn('meal_types'));
        }
    }
};

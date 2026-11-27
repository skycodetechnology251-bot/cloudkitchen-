<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Branches (kitchens) get country / state / city / pincode like zones. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchens', function (Blueprint $table) {
            if (! Schema::hasColumn('kitchens', 'city')) {
                $table->string('country', 80)->default('India')->after('address');
                $table->string('country_code', 2)->default('IN')->after('country');
                $table->string('state', 80)->nullable()->after('country_code');
                $table->string('city', 80)->nullable()->after('state');
                $table->string('pincode', 12)->nullable()->after('city');
            }
        });
        DB::table('kitchens')->whereNull('city')->update(['state' => 'Tamil Nadu', 'city' => 'Chennai']);
    }

    public function down(): void
    {
        Schema::table('kitchens', function (Blueprint $table) {
            $table->dropColumn(['country', 'country_code', 'state', 'city', 'pincode']);
        });
    }
};

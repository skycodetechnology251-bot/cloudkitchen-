<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Version 1.5: zones get a country / state / city, a full address and a centre point on the map. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (! Schema::hasColumn('zones', 'country')) {
                $table->string('country', 80)->default('India')->after('slug');
                $table->string('country_code', 2)->default('IN')->after('country');
                $table->string('state', 80)->nullable()->after('country_code');
                $table->string('city', 80)->nullable()->after('state');
                $table->string('address')->nullable()->after('city');
                $table->string('pincode', 12)->nullable()->after('address');
                $table->decimal('center_lat', 10, 7)->nullable()->after('pincode');
                $table->decimal('center_lng', 10, 7)->nullable()->after('center_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('zones', fn (Blueprint $t) => $t->dropColumn(['country', 'country_code', 'state', 'city', 'address', 'pincode', 'center_lat', 'center_lng']));
    }
};

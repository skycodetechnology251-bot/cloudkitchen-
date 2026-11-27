<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Version 1.3: sign-up asks "I'm ordering for: myself / my family". */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ordering_for')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ordering_for', 10)->nullable()->after('date_of_birth');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('ordering_for'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Version 1.2: the counter POS is open to every staff role (riders use their own app). Runs once. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permission_role')) {
            return;
        }
        $pos = DB::table('permissions')->where('slug', 'pos.use')->value('id');
        if (! $pos) {
            return; // fresh install: the seeder gives every role its POS permission
        }
        foreach (DB::table('roles')->where('slug', '!=', 'rider')->pluck('id') as $role) {
            DB::table('permission_role')->insertOrIgnore(['permission_id' => $pos, 'role_id' => $role]);
        }
    }

    public function down(): void {}
};

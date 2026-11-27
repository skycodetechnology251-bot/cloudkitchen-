<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Version 1.7: branches only see their own data. "See every branch" (head office) is a permission:
 * admin has it; give it to other roles in Admin → Roles & permissions if they should see all branches.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! DB::table('permissions')->exists()) {
            return; // fresh install: the seeder adds it
        }
        $id = DB::table('permissions')->where('slug', 'branches.all')->value('id')
            ?? DB::table('permissions')->insertGetId(['slug' => 'branches.all', 'name' => 'See & manage every branch (head office)', 'group' => 'branches', 'sensitive' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach (DB::table('roles')->where('slug', 'admin')->pluck('id') as $role) {
            DB::table('permission_role')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role]);
        }
        DB::table('permissions')->where('slug', 'zones.edit')->update(['name' => 'Create & edit branches and zones']);
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'branches.all')->delete();
    }
};

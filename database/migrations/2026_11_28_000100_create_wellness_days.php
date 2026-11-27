<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Wellness coach: one row per customer per day (water glasses, workout minutes). Only kept with consent. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wellness_days')) {
            Schema::create('wellness_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('day');
                $table->unsignedTinyInteger('glasses')->default(0);
                $table->unsignedTinyInteger('glass_goal')->default(8);
                $table->unsignedSmallInteger('workout_minutes')->default(0);
                $table->unsignedSmallInteger('workout_goal')->default(30);
                $table->json('workouts')->nullable();           // [{"type":"walk","minutes":20,"intensity":"moderate"}]
                $table->timestamps();
                $table->unique(['user_id', 'day']);
            });
        }
        if (! Schema::hasColumn('users', 'coach_consent_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->timestamp('coach_consent_at')->nullable());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wellness_days');
        if (Schema::hasColumn('users', 'coach_consent_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('coach_consent_at'));
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('diet', ['veg', 'egg', 'nonveg'])->nullable();
            $table->json('allergies')->nullable();
            $table->text('conditions')->nullable();        // encrypted JSON, only with consent
            $table->timestamp('conditions_consent_at')->nullable();
            $table->string('consent_version', 10)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->enum('spice', ['mild', 'medium', 'spicy'])->nullable();
            $table->string('dislikes')->nullable();
            $table->string('activity')->nullable();
            $table->timestamps();
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');                     // health_suggestions, marketing_whatsapp, ...
            $table->string('policy_version', 10);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 30);
            $table->string('flat');
            $table->string('building')->nullable();
            $table->string('street');
            $table->string('area');
            $table->string('landmark')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('pincode', 10);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone', 15)->nullable();
            $table->string('instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 10);
            $table->string('token')->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('topic');                       // order, reminder, plan, offers, weekly
            $table->string('channel');                     // push, whatsapp, sms, email
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'topic', 'channel']);
        });

        Schema::create('meal_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('meal', ['breakfast', 'lunch', 'dinner']);
            $table->date('service_date');
            $table->enum('response', ['yes', 'no']);
            $table->string('source', 20)->default('app');
            $table->timestamps();
            $table->unique(['user_id', 'meal', 'service_date']);
        });
    }

    public function down(): void
    {
        foreach (['meal_responses', 'notification_preferences', 'device_tokens', 'addresses', 'consents', 'health_profiles', 'otp_codes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

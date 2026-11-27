<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->foreignId('kitchen_id')->constrained();
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['offered', 'accepted', 'declined', 'picked_up', 'completed', 'cancelled'])->default('offered');
            $table->string('pickup_code', 6);
            $table->timestamp('offer_expires_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained();
            $table->unsignedTinyInteger('sequence');
            $table->enum('status', ['pending', 'arrived', 'delivered', 'failed'])->default('pending');
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->string('problem')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->enum('placement', ['home', 'login', 'popup']);
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('tag')->nullable();
            $table->string('cta')->nullable();
            $table->string('link')->nullable();
            $table->json('design')->nullable();                   // layout, colours, show_code, show_points
            $table->string('image_path')->nullable();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('zone_ids')->nullable();
            $table->enum('diet_scope', ['any', 'veg', 'egg', 'nonveg'])->default('any');
            $table->string('frequency', 20)->default('always');  // popup: once, daily
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('taps')->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_id')->constrained();
            $table->string('category');                           // ingredients, salary, rent, gas, packaging, delivery, marketing, other
            $table->string('description');
            $table->string('supplier')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 10)->nullable();
            $table->unsignedInteger('amount_paise');
            $table->unsignedInteger('paid_paise')->default(0);
            $table->date('spent_on');
            $table->string('bill_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kitchen_id', 'spent_on']);
        });
    }

    public function down(): void
    {
        foreach (['expenses', 'banners', 'delivery_stops', 'delivery_batches'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

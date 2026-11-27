<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('polygon');                       // [[lat,lng],...]
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft', 'testing', 'live', 'paused', 'closed'])->default('draft');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('went_live_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kitchens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('code', 8)->unique();           // used in invoice series, e.g. ADY
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('gstin', 15)->nullable();
            $table->string('fssai', 20)->nullable();
            $table->string('phone', 15)->nullable();
            $table->boolean('is_open')->default(true);
            $table->unsignedInteger('service_radius_m')->default(10000);
            $table->timestamps();
        });

        Schema::create('kitchen_zone', function (Blueprint $table) {
            $table->foreignId('kitchen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->primary(['kitchen_id', 'zone_id']);
        });

        Schema::create('kitchen_user', function (Blueprint $table) {
            $table->foreignId('kitchen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['kitchen_id', 'user_id']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('diet', ['veg', 'egg', 'nonveg'])->default('veg');
            $table->unsignedInteger('price_paise');
            $table->string('sac', 8)->default('996331');
            $table->unsignedSmallInteger('kcal')->nullable();
            $table->unsignedSmallInteger('protein_g')->nullable();
            $table->unsignedSmallInteger('carbs_g')->nullable();
            $table->unsignedSmallInteger('fat_g')->nullable();
            $table->unsignedSmallInteger('fibre_g')->nullable();
            $table->enum('sodium', ['low', 'medium', 'high'])->default('medium');
            $table->json('balance')->nullable();           // {"grains":35,"protein":25,"veg":30,"dairy":10}
            $table->json('ingredients')->nullable();
            $table->json('allergens')->nullable();         // ["milk","gluten"]
            $table->json('suitable_for')->nullable();      // reviewed condition tags
            $table->string('image_path')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('daily_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->enum('meal', ['breakfast', 'lunch', 'dinner'])->default('lunch');
            $table->unsignedInteger('price_paise');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->enum('status', ['open', 'paused', 'closed'])->default('open');
            $table->time('cutoff_at')->default('10:45:00');
            $table->timestamps();
            $table->unique(['kitchen_id', 'dish_id', 'service_date', 'meal']);
            $table->index(['service_date', 'meal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_menus');
        Schema::dropIfExists('dishes');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('kitchen_user');
        Schema::dropIfExists('kitchen_zone');
        Schema::dropIfExists('kitchens');
        Schema::dropIfExists('zones');
    }
};

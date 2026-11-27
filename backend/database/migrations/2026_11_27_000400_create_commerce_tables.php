<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('title');
            $table->enum('type', ['percent', 'flat', 'free_delivery', 'bonus_points']);
            $table->unsignedInteger('value');                     // percent, paise, or multiplier x100
            $table->unsignedInteger('min_order_paise')->default(0);
            $table->unsignedInteger('max_discount_paise')->nullable();
            $table->unsignedInteger('uses_per_customer')->default(1);
            $table->unsignedInteger('total_uses')->nullable();
            $table->unsignedInteger('used')->default(0);
            $table->json('zone_ids')->nullable();
            $table->json('days')->nullable();                     // [1..7]
            $table->enum('diet_scope', ['any', 'veg', 'egg', 'nonveg'])->default('any');
            $table->boolean('new_customers_only')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('kitchen_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->json('address_snapshot')->nullable();
            $table->enum('channel', ['app', 'web', 'pos'])->default('app');
            $table->enum('order_type', ['delivery', 'takeaway', 'dine_in'])->default('delivery');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('service_date');
            $table->enum('meal', ['breakfast', 'lunch', 'dinner'])->default('lunch');
            $table->string('slot', 20)->nullable();              // 12:00-13:00
            $table->enum('status', ['awaiting_payment', 'confirmed', 'accepted', 'preparing', 'packed', 'picked_up', 'delivered', 'cancelled', 'failed'])->default('awaiting_payment');
            $table->enum('payment_status', ['pending', 'paid', 'partially_refunded', 'refunded', 'failed'])->default('pending');
            $table->unsignedInteger('subtotal_paise');
            $table->unsignedInteger('discount_paise')->default(0);
            $table->unsignedInteger('delivery_paise')->default(0);
            $table->unsignedInteger('taxable_paise');
            $table->unsignedInteger('cgst_paise');
            $table->unsignedInteger('sgst_paise');
            $table->integer('round_off_paise')->default(0);
            $table->unsignedInteger('total_paise');
            $table->unsignedInteger('wallet_paise')->default(0);
            $table->unsignedInteger('points_used')->default(0);
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_otp_hash')->nullable();
            $table->text('delivery_code')->nullable();            // encrypted, shown only to the customer
            $table->timestamp('reserved_until')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['kitchen_id', 'service_date', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained();
            $table->foreignId('daily_menu_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sac', 8);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_price_paise');
            $table->unsignedInteger('line_total_paise');
            $table->json('allergens')->nullable();
            $table->string('handling_note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');                            // order or wallet top-up
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('method', ['razorpay', 'wallet', 'cash', 'card_pos', 'upi_pos']);
            $table->unsignedInteger('amount_paise');
            $table->string('gateway_order_id')->nullable()->unique();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->enum('status', ['created', 'captured', 'failed', 'refunded'])->default('created');
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->unique();
            $table->string('type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_paise');
            $table->enum('destination', ['source', 'wallet']);
            $table->string('reason');
            $table->string('gateway_refund_id')->nullable();
            $table->enum('status', ['initiated', 'processing', 'succeeded', 'failed'])->default('initiated');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained();
            $table->foreignId('kitchen_id')->constrained();
            $table->string('number', 30)->unique();               // DT/ADY/26-27/00412
            $table->string('series', 20);
            $table->unsignedInteger('sequence');
            $table->string('financial_year', 5);
            $table->json('snapshot');                            // seller, buyer, lines, totals frozen
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->unique(['series', 'sequence']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('balance_paise')->default(0);
            $table->unsignedInteger('low_balance_paise')->default(15000);
            $table->boolean('pay_first')->default(true);
            $table->boolean('auto_topup')->default(false);
            $table->unsignedInteger('auto_topup_paise')->default(100000);
            $table->timestamp('last_low_alert_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['topup', 'order', 'refund', 'adjustment']);
            $table->integer('amount_paise');                      // + credit, - debit
            $table->unsignedInteger('balance_after_paise');
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason');                             // order, signup, referral, redeem, expiry
            $table->integer('points');
            $table->nullableMorphs('reference');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['point_transactions', 'wallet_transactions', 'wallets', 'invoices', 'refunds', 'webhook_events', 'payments', 'order_events', 'order_items', 'orders', 'offers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

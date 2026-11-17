<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Promo codes, their redemptions, and the discount stored on the order. Money is in minor units. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            // Exactly one of percent / amount is set
            $table->decimal('percent', 5, 2)->nullable();
            $table->integer('amount')->nullable();
            $table->integer('max_discount')->nullable(); // cap for percent codes
            $table->integer('min_order')->default(0);
            $table->foreignUuid('restaurant_id')->nullable()->constrained()->cascadeOnDelete(); // null = any restaurant
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); // total redemptions
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promo_code_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('discount_amount');
            $table->timestamps();

            $table->index(['promo_code_id', 'user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('promo_code_id')->nullable();
            $table->string('promo_code', 40)->nullable(); // snapshot: stays readable if the code is deleted
            $table->integer('discount_amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['promo_code_id', 'promo_code', 'discount_amount']);
        });
        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');
    }
};

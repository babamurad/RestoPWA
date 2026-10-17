<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->uuid('user_id');
            $table->uuid('restaurant_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('vendor_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->index(['restaurant_id', 'is_hidden', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

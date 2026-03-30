<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('user_id');
            $table->string('status', 20)->default('pending');
            $table->jsonb('address');
            $table->jsonb('items');
            $table->decimal('total', 10, 2);
            $table->string('payment_status', 20)->default('pending');
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('restaurants')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('vendor_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

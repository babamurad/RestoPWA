<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            // It maps to courier_profiles which is used by Courier model.
            $table->foreignUuid('courier_id')->constrained('courier_profiles')->cascadeOnDelete();
            
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid

            $table->timestamps();
            
            $table->unique(['order_id', 'courier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_earnings');
    }
};

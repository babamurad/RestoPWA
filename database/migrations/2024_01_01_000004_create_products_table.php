<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->jsonb('modifiers')->nullable();
            $table->string('image')->nullable();
            $table->integer('weight_g')->nullable();
            $table->integer('kcal')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('restaurants')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');

            $table->index('vendor_id');
            $table->index('category_id');
            $table->index('is_available');
            $table->index(['vendor_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

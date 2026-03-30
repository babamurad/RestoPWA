<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('vendor_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('restaurants')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');

            $table->index('vendor_id');
            $table->index('parent_id');
            $table->index('sort_order');
            $table->index(['vendor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

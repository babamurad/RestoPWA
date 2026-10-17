<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // City districts: one shared list for the whole platform, drawn by the admin.
        Schema::create('districts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->json('boundary')->nullable(); // GeoJSON MultiPolygon, [lon, lat]
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Each restaurant prices the districts it delivers to (distance from it differs).
        Schema::create('restaurant_delivery_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('restaurant_id');
            $table->uuid('district_id');
            $table->unsignedInteger('price'); // minor units (тыйын), via MoneyCast
            $table->timestamps();

            $table->unique(['restaurant_id', 'district_id']);
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_delivery_prices');
        Schema::dropIfExists('districts');
    }
};

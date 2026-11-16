<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cuisine tags of a restaurant (catalog filters) and the customers' favourite restaurants. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // jsonb: PostgreSQL cannot compare plain json values (DISTINCT, GROUP BY)
            $table->jsonb('tags')->nullable();
        });

        Schema::create('user_favorites', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['user_id', 'restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorites');

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};

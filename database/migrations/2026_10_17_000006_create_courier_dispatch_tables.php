<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A courier can work for several restaurants; no rows = general pool.
        Schema::create('courier_restaurant', function (Blueprint $table) {
            $table->uuid('courier_id');
            $table->uuid('restaurant_id');
            $table->timestamps();

            $table->primary(['courier_id', 'restaurant_id']);
            $table->foreign('courier_id')->references('id')->on('courier_profiles')->cascadeOnDelete();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });

        // Carry over the old single binding (courier_profiles.vendor_id is no longer read).
        DB::table('courier_profiles')->whereNotNull('vendor_id')->orderBy('id')
            ->each(function ($courier) {
                DB::table('courier_restaurant')->insertOrIgnore([
                    'courier_id' => $courier->id,
                    'restaurant_id' => $courier->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('courier_profiles', function (Blueprint $table) {
            // Fair rotation: the courier who waited longest gets the next offer
            $table->timestamp('last_assigned_at')->nullable();
        });

        // The vendor kanban used to set "ready", which couriers never saw (they list ready_for_pickup)
        DB::table('orders')->where('status', 'ready')->update(['status' => 'ready_for_pickup']);

        // Automatic dispatch: one row per order offered to a courier
        Schema::create('courier_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('courier_id');
            $table->string('status', 16); // pending | accepted | declined | expired | cancelled
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('courier_id')->references('id')->on('courier_profiles')->cascadeOnDelete();
            $table->index(['order_id', 'status']);
            $table->index(['courier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_offers');

        Schema::table('courier_profiles', function (Blueprint $table) {
            $table->dropColumn('last_assigned_at');
        });

        Schema::dropIfExists('courier_restaurant');
    }
};

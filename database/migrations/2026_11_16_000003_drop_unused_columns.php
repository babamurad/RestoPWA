<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - restaurants.commission_rate: never read; the platform commission is commission_percent.
 * - courier_profiles.vendor_id: replaced by the courier_restaurant table (its data was
 *   carried over by 2026_10_17_000006) and not read by the code since.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });

        Schema::table('courier_profiles', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(15.00);
        });

        Schema::table('courier_profiles', function (Blueprint $table) {
            $table->uuid('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('restaurants')->nullOnDelete();
        });
    }
};

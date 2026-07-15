<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('courier_fixed_fee', 10, 2)->default(0)->after('min_order');
            $table->decimal('courier_percent_fee', 5, 2)->default(0)->after('courier_fixed_fee');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['courier_fixed_fee', 'courier_percent_fee']);
        });
    }
};

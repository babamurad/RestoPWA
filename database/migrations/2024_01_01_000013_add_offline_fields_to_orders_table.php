<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_time', 50)->nullable()->after('delivery_fee');
            $table->string('payment_method', 20)->default('card')->after('delivery_time');
            $table->text('comment')->nullable()->after('payment_method');
            $table->boolean('is_offline')->default(false)->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_time', 'payment_method', 'comment', 'is_offline']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->default(0)->after('min_order');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('commission_amount')->default(0)->after('delivery_fee'); // money
            $table->foreignUuid('vendor_settlement_id')->nullable()->constrained()->nullOnDelete()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['vendor_settlement_id']);
            $table->dropColumn(['commission_amount', 'vendor_settlement_id']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('provider', 32);            // cash, later: bank/online provider code
            $table->string('provider_transaction_id')->nullable();
            $table->string('type', 16);                // payment | refund
            $table->string('status', 16);              // pending | succeeded | failed | cancelled
            $table->unsignedBigInteger('amount');      // minor units (тыйын), like orders.total
            $table->string('currency', 3)->default('TMT');
            $table->json('raw_payload')->nullable();   // provider callback body, for audits
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index(['order_id', 'type', 'status']);
        });

        // Old free-form values -> PaymentStatus enum
        DB::table('orders')->where('payment_status', 'pending')->update(['payment_status' => 'unpaid']);
        DB::table('orders')->where('payment_status', 'failed')->update(['payment_status' => 'payment_failed']);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('pending')->change();
        });

        DB::table('orders')->where('payment_status', 'unpaid')->update(['payment_status' => 'pending']);
        DB::table('orders')->where('payment_status', 'payment_failed')->update(['payment_status' => 'failed']);

        Schema::dropIfExists('payment_transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Key of config('billing.plans'); null until the admin picks one
            $table->string('subscription_plan', 20)->nullable();
            // Last day the subscription is paid for
            $table->date('subscription_paid_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_paid_until']);
        });
    }
};

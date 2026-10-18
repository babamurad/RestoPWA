<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every order the server refused (outside zone, restaurant closed…), for admin analytics
        Schema::create('order_rejections', function (Blueprint $table) {
            $table->id();
            $table->string('reason', 40)->index();
            // No foreign keys: rejected payloads may carry unknown restaurants
            $table->uuid('vendor_id')->nullable()->index();
            $table->uuid('user_id')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_rejections');
    }
};

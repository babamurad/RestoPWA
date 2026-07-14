<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->uuid('vendor_id')->nullable();
            
            $table->string('vehicle_type')->default('walking'); // walking, bike, car
            $table->string('status')->default('offline'); // offline, online, busy
            
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lon', 11, 8)->nullable();
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('restaurants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};

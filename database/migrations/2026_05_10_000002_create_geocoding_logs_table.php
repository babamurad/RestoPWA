<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geocoding_logs', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 100)->nullable()->index();
            $table->string('user_id', 100)->nullable()->index();
            $table->string('vendor_id', 100)->nullable()->index();
            $table->string('provider', 50)->nullable();
            $table->string('query', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            $table->string('status', 50)->index();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geocoding_logs');
    }
};

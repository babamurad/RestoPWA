<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_places', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('city', 100)->default('Туркменабат');
            $table->string('name', 255);
            $table->json('aliases')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);
            $table->string('type', 50)->default('other');
            $table->unsignedInteger('popularity')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('city');
            $table->index('type');
            $table->index('popularity');
            $table->index('is_verified');
            $table->fullText('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_places');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('commission_rate', 5, 2)->default(15.00);
            $table->uuid('owner_id');
            $table->timestamps();

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('owner_id');
            $table->index('is_active');
        });

        DB::statement("SELECT AddGeometryColumn('restaurants', 'delivery_zones', 4326, 'POLYGON', 2)");
        DB::statement('CREATE INDEX restaurants_delivery_zones_gist ON restaurants USING GIST (delivery_zones)');
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};

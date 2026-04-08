<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasPostGis = $this->postGisAvailable();

        Schema::create('restaurants', function (Blueprint $table) use ($hasPostGis) {
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

            if (! $hasPostGis) {
                $table->text('delivery_zones')->nullable();
            }
        });

        if ($hasPostGis) {
            DB::statement("SELECT AddGeometryColumn('restaurants', 'delivery_zones', 4326, 'POLYGON', 2)");
            DB::statement('CREATE INDEX restaurants_delivery_zones_gist ON restaurants USING GIST (delivery_zones)');
        }
    }

    protected function postGisAvailable(): bool
    {
        try {
            $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'addgeometrycolumn' LIMIT 1");

            return ! empty($result);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};

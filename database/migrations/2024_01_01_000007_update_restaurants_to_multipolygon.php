<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->postGisAvailable()) {
            // Drop previous index
            DB::statement('DROP INDEX IF EXISTS restaurants_delivery_zones_gist');

            // Re-add as MULTIPOLYGON
            // We use DropGeometryColumn and AddGeometryColumn for PostGIS 2.x+
            DB::statement("SELECT DropGeometryColumn('restaurants', 'delivery_zones')");
            DB::statement("SELECT AddGeometryColumn('restaurants', 'delivery_zones', 4326, 'MULTIPOLYGON', 2)");

            // Re-create index
            DB::statement('CREATE INDEX restaurants_delivery_zones_gist ON restaurants USING GIST (delivery_zones)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->postGisAvailable()) {
            DB::statement('DROP INDEX IF EXISTS restaurants_delivery_zones_gist');
            DB::statement("SELECT DropGeometryColumn('restaurants', 'delivery_zones')");
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
};

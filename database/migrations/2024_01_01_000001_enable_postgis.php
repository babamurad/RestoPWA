<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (Exception $e) {
            Log::warning('PostGIS extension not available: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP EXTENSION IF EXISTS postgis CASCADE');
        } catch (Exception $e) {
            Log::warning('Could not drop PostGIS extension: '.$e->getMessage());
        }
    }
};

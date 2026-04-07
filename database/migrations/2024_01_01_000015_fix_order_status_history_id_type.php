<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE order_status_history DROP CONSTRAINT IF EXISTS order_status_history_pkey');
        DB::statement('ALTER TABLE order_status_history DROP COLUMN IF EXISTS id');
        DB::statement('ALTER TABLE order_status_history ADD COLUMN id uuid DEFAULT gen_random_uuid() PRIMARY KEY');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE order_status_history DROP COLUMN IF EXISTS id');
        DB::statement('ALTER TABLE order_status_history ADD COLUMN id bigserial PRIMARY KEY');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_status_history DROP CONSTRAINT IF EXISTS order_status_history_pkey');
            DB::statement('ALTER TABLE order_status_history DROP COLUMN IF EXISTS id');
            DB::statement('ALTER TABLE order_status_history ADD COLUMN id uuid DEFAULT gen_random_uuid() PRIMARY KEY');
        } else {
            Schema::table('order_status_history', function ($table) {
                $table->dropPrimary();
            });
            Schema::table('order_status_history', function ($table) {
                $table->uuid('id')->primary()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_status_history DROP COLUMN IF EXISTS id');
            DB::statement('ALTER TABLE order_status_history ADD COLUMN id bigserial PRIMARY KEY');
        } else {
            Schema::table('order_status_history', function ($table) {
                $table->dropPrimary();
            });
            Schema::table('order_status_history', function ($table) {
                $table->bigIncrements('id')->change();
            });
        }
    }
};

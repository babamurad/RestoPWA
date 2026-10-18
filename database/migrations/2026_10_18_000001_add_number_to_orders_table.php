<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** First order gets №1001: short numbers that don't look like a test shop */
    private const START = 1000;

    public function up(): void
    {
        // Counters handed out under a row lock (works the same on Postgres and SQLite)
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable()->unique()->after('id');
        });

        // Existing orders are numbered in the order they were placed
        $number = self::START;
        DB::table('orders')->orderBy('created_at')->orderBy('id')->select('id')
            ->chunk(500, function ($orders) use (&$number) {
                foreach ($orders as $order) {
                    DB::table('orders')->where('id', $order->id)->update(['number' => ++$number]);
                }
            });

        DB::table('sequences')->insert(['name' => 'orders', 'value' => $number]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->dropColumn('number');
        });

        Schema::dropIfExists('sequences');
    }
};

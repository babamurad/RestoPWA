<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
            $table->string('cover_image')->nullable()->after('image');
            $table->decimal('rating', 3, 1)->nullable()->after('cover_image');
            $table->integer('review_count')->default(0)->after('rating');
            $table->string('delivery_time')->nullable()->after('review_count');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('delivery_time');
            $table->integer('min_order')->default(0)->after('delivery_fee');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'image',
                'cover_image',
                'rating',
                'review_count',
                'delivery_time',
                'delivery_fee',
                'min_order',
            ]);
        });
    }
};

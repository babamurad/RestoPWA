<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->after('password');
        });

        // Migrate data
        \Illuminate\Support\Facades\DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        $vendorIds = \Illuminate\Support\Facades\DB::table('restaurants')->whereNotNull('vendor_id')->pluck('vendor_id');
        $ownerIds = \Illuminate\Support\Facades\DB::table('restaurants')->whereNotNull('owner_id')->pluck('owner_id');

        \Illuminate\Support\Facades\DB::table('users')->whereIn('id', $vendorIds)->where('role', '!=', 'admin')->update(['role' => 'restaurateur']);
        \Illuminate\Support\Facades\DB::table('users')->whereIn('id', $ownerIds)->where('role', '!=', 'admin')->update(['role' => 'restaurateur']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        \Illuminate\Support\Facades\DB::table('users')->where('role', 'admin')->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

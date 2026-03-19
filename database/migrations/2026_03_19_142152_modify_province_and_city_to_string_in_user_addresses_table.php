<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clear existing data that will conflict with foreign key constraints
        // This is safe in development/testing environment
        DB::table('user_addresses')->truncate();

        Schema::table('user_addresses', function (Blueprint $table) {
            // Change columns to string(13) to match regions.code
            $table->string('province_id', 13)->change();
            $table->string('city_id', 13)->change();

            // Add foreign key constraints to regions table
            $table->foreign('province_id')
                ->references('code')
                ->on('regions')
                ->nullOnDelete();

            $table->foreign('city_id')
                ->references('code')
                ->on('regions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);

            // Revert columns to unsignedBigInteger
            $table->unsignedBigInteger('province_id')->change();
            $table->unsignedBigInteger('city_id')->change();
        });
    }
};

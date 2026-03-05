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
        // Update Trainer Table
        Schema::table('fb_tbl_trainer', function (Blueprint $table) {
            // Add string field for consistency
            if (!Schema::hasColumn('fb_tbl_trainer', 'zone')) {
                $table->string('zone')->nullable()->after('city');
            }
            
            // Add Master ID fields
            $table->foreignId('state_id')->nullable()->after('state')->constrained('fb_tbl_state')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->after('city')->constrained('fb_tbl_city')->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->after('city_id')->constrained('fb_tbl_zone')->onDelete('set null');
        });

        // Update Client Table
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            // Add string field for consistency
            if (!Schema::hasColumn('fb_tbl_client', 'zone')) {
                $table->string('zone')->nullable()->after('city');
            }

            // Add Master ID fields
            $table->foreignId('state_id')->nullable()->after('state')->constrained('fb_tbl_state')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->after('city')->constrained('fb_tbl_city')->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->after('city_id')->constrained('fb_tbl_zone')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_trainer', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['state_id', 'city_id', 'zone_id', 'zone']);
        });

        Schema::table('fb_tbl_client', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['state_id', 'city_id', 'zone_id', 'zone']);
        });
    }
};

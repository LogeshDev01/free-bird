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
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            // Consolidated into base
        });

        Schema::table('fb_tbl_diet_plan_category', function (Blueprint $table) {
            // Consolidated into base
        });

        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            // Consolidated into base
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            $table->dropForeign(['current_subscription_id']);
            $table->dropColumn('current_subscription_id');
        });

        Schema::table('fb_tbl_diet_plan_category', function (Blueprint $table) {
            $table->dropColumn('minimum_plan_tier');
        });

        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            $table->dropColumn('minimum_plan_tier');
        });
    }
};

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
        Schema::table('fb_tbl_diet_plan_category', function (Blueprint $table) {
            // Consolidated into base
        });

        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            // Consolidated into base
        });
    }

    public function down(): void
    {
        //
    }
};

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
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->nullableMorphs('assigned_by');
            $table->json('custom_sets')->nullable()->after('workout_id')->comment('Custom sets data: [{reps, weight, weight_unit, rest}]');
            // Make original trainer_id nullable if not already
            $table->unsignedBigInteger('trainer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropMorphs('assigned_by');
            $table->dropColumn('custom_sets');
        });
    }
};

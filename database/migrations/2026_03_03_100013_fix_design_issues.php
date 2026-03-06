<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Complete remaining design fixes.
     * Previous steps already applied:
     * - Trainer: status + soft deletes ✅  
     * - Client: soft deletes ✅ (idempotent check)
     * - Trainer-Client: new unique ✅
     * Remaining:
     * - Session/Workout/DietPlan soft deletes
     * - TrainerRating: remove month column
     */
    public function up(): void
    {
            // Consolidated into base migrations
    }

    public function down(): void
    {
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fb_tbl_trainer_client', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fb_tbl_session', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fb_tbl_workout', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fb_tbl_diet_plan', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fb_tbl_trainer_rating', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropIndex('trainer_rating_monthly_idx');
            $table->date('month')->nullable()->comment('For monthly satisfaction tracking');
            $table->index(['trainer_id', 'month']);
            $table->foreign('trainer_id')->references('id')->on('fb_tbl_trainer')->onDelete('cascade');
        });
    }
};

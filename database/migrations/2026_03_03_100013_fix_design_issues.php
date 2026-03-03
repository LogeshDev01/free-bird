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
        // 1. Client: add soft deletes (idempotent)
        if (!Schema::hasColumn('fb_tbl_client', 'deleted_at')) {
            Schema::table('fb_tbl_client', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 2. Trainer-Client pivot: already fixed in partial run (idempotent skip)
        // The trainer_client_enrollment_unique is already in place.
        if (!Schema::hasColumn('fb_tbl_trainer_client', 'deleted_at')) {
            Schema::table('fb_tbl_trainer_client', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 3. Session: add soft deletes
        if (!Schema::hasColumn('fb_tbl_session', 'deleted_at')) {
            Schema::table('fb_tbl_session', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 4. Workout: add soft deletes
        if (!Schema::hasColumn('fb_tbl_workout', 'deleted_at')) {
            Schema::table('fb_tbl_workout', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 5. Diet Plan: add soft deletes
        if (!Schema::hasColumn('fb_tbl_diet_plan', 'deleted_at')) {
            Schema::table('fb_tbl_diet_plan', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 6. Trainer Rating: drop FK first, then drop index, then drop column
        if (Schema::hasColumn('fb_tbl_trainer_rating', 'month')) {
            // Drop FK on trainer_id that depends on the composite index
            Schema::table('fb_tbl_trainer_rating', function (Blueprint $table) {
                $table->dropForeign(['trainer_id']);
            });

            // Now drop the index
            Schema::table('fb_tbl_trainer_rating', function (Blueprint $table) {
                $table->dropIndex('fb_tbl_trainer_rating_trainer_id_month_index');
            });

            // Drop the column
            Schema::table('fb_tbl_trainer_rating', function (Blueprint $table) {
                $table->dropColumn('month');
            });

            // Re-add FK + new index using created_at
            Schema::table('fb_tbl_trainer_rating', function (Blueprint $table) {
                $table->foreign('trainer_id')->references('id')->on('fb_tbl_trainer')->onDelete('cascade');
                $table->index(['trainer_id', 'created_at'], 'trainer_rating_monthly_idx');
            });
        }
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

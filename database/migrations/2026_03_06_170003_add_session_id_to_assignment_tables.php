<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add session_id (nullable FK) to both assignment tables.
     *
     * Nullable so existing rows are not broken.
     * When a new assignment is created, the session_id can be passed in
     * to link the workout/diet directly to a specific session.
     *
     * ON DELETE SET NULL — if a session is deleted, assignments remain
     * but lose the session link (history preserved).
     */
    public function up(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')
                  ->nullable()
                  ->after('batch_id');

            $table->foreign('session_id')
                  ->references('id')
                  ->on('fb_tbl_session')
                  ->onDelete('set null');

            $table->index('session_id');
        });

        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')
                  ->nullable()
                  ->after('trainer_id');

            $table->foreign('session_id')
                  ->references('id')
                  ->on('fb_tbl_session')
                  ->onDelete('set null');

            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });

        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change trainer_id FK on fb_tbl_workout_assignment
     * from ON DELETE CASCADE → ON DELETE RESTRICT
     *
     * Reason: CASCADE was silently wiping all client workout history
     * whenever a trainer account was deleted. RESTRICT prevents the
     * deletion if any assignments exist, forcing explicit cleanup first.
     */
    public function up(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            // Drop the old FK constraint
            $table->dropForeign(['trainer_id']);

            // Re-add with RESTRICT so we can't accidentally delete a trainer
            // and lose all their client workout history
            $table->foreign('trainer_id')
                  ->references('id')
                  ->on('fb_tbl_trainer')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);

            // Restore original CASCADE behaviour
            $table->foreign('trainer_id')
                  ->references('id')
                  ->on('fb_tbl_trainer')
                  ->onDelete('cascade');
        });
    }
};

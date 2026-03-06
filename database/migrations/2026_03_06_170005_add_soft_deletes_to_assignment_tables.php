<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft deletes (deleted_at) to both assignment tables.
     *
     * Instead of hard-deleting assignments (which permanently wipes client
     * workout / diet history), records are now soft-deleted.
     * Eloquent SoftDeletes trait is added to both models; all existing queries
     * automatically exclude soft-deleted rows via the global scope.
     */
    public function up(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->softDeletes()->after('notes');
        });

        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->softDeletes()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

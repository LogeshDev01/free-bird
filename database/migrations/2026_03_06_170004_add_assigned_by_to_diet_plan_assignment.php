<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add assigned_by polymorphic columns to fb_tbl_diet_plan_assignment.
     *
     * WorkoutAssignment already has these (assigned_by_id + assigned_by_type).
     * This brings DietPlanAssignment to parity so admin-sourced diet assignments
     * are tracked with a full audit trail.
     */
    public function up(): void
    {
        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_by_id')
                  ->nullable()
                  ->after('trainer_id');

            $table->string('assigned_by_type')
                  ->nullable()
                  ->after('assigned_by_id')
                  ->comment('App\\Models\\Trainer or App\\Models\\User (Admin)');

            $table->index(['assigned_by_type', 'assigned_by_id'], 'diet_assignment_assignedby_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->dropIndex('diet_assignment_assignedby_idx');
            $table->dropColumn(['assigned_by_id', 'assigned_by_type']);
        });
    }
};

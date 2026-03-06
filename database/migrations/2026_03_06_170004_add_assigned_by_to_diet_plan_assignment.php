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
    public function down(): void
    {
        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            //
        });
    }
};

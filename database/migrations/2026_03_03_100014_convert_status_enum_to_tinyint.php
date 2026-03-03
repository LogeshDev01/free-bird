<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert all enum status columns to tinyInteger (default 1).
     *
     * Status value mapping (matches model constants):
     * ─────────────────────────────────────────────────────────
     * Trainer:              1=active, 0=inactive, 2=suspended
     * Client:               1=active, 0=inactive
     * TrainerClient:        1=active, 0=inactive, 2=completed
     * Session:              1=scheduled, 2=completed, 3=cancelled, 4=no_show
     * WorkoutAssignment:    1=pending, 2=in_progress, 3=completed
     * DietPlanAssignment:   1=pending, 2=in_progress, 3=completed
     */
    public function up(): void
    {
        // 1. fb_tbl_trainer: enum → tinyInt
        DB::statement("ALTER TABLE fb_tbl_trainer ADD COLUMN status_new TINYINT DEFAULT 1 AFTER emergency_phone");
        DB::statement("UPDATE fb_tbl_trainer SET status_new = CASE
            WHEN status = 'active' THEN 1
            WHEN status = 'inactive' THEN 0
            WHEN status = 'suspended' THEN 2
            ELSE 1 END");
        DB::statement("ALTER TABLE fb_tbl_trainer DROP COLUMN status");
        DB::statement("ALTER TABLE fb_tbl_trainer CHANGE status_new status TINYINT DEFAULT 1 COMMENT '1=active, 0=inactive, 2=suspended'");

        // 2. fb_tbl_client: enum → tinyInt
        DB::statement("ALTER TABLE fb_tbl_client ADD COLUMN status_new TINYINT DEFAULT 1 AFTER emergency_phone");
        DB::statement("UPDATE fb_tbl_client SET status_new = CASE
            WHEN status = 'active' THEN 1
            WHEN status = 'inactive' THEN 0
            ELSE 1 END");
        DB::statement("ALTER TABLE fb_tbl_client DROP COLUMN status");
        DB::statement("ALTER TABLE fb_tbl_client CHANGE status_new status TINYINT DEFAULT 1 COMMENT '1=active, 0=inactive'");

        // 3. fb_tbl_trainer_client: enum → tinyInt
        DB::statement("ALTER TABLE fb_tbl_trainer_client ADD COLUMN status_new TINYINT DEFAULT 1 AFTER client_id");
        DB::statement("UPDATE fb_tbl_trainer_client SET status_new = CASE
            WHEN status = 'active' THEN 1
            WHEN status = 'inactive' THEN 0
            WHEN status = 'completed' THEN 2
            ELSE 1 END");
        DB::statement("ALTER TABLE fb_tbl_trainer_client DROP COLUMN status");
        DB::statement("ALTER TABLE fb_tbl_trainer_client CHANGE status_new status TINYINT DEFAULT 1 COMMENT '1=active, 0=inactive, 2=completed'");

        // 4. fb_tbl_session: enum → tinyInt
        DB::statement("ALTER TABLE fb_tbl_session ADD COLUMN status_new TINYINT DEFAULT 1 AFTER location");
        DB::statement("UPDATE fb_tbl_session SET status_new = CASE
            WHEN status = 'scheduled' THEN 1
            WHEN status = 'completed' THEN 2
            WHEN status = 'cancelled' THEN 3
            WHEN status = 'no_show' THEN 4
            ELSE 1 END");
        DB::statement("ALTER TABLE fb_tbl_session DROP COLUMN status");
        DB::statement("ALTER TABLE fb_tbl_session CHANGE status_new status TINYINT DEFAULT 1 COMMENT '1=scheduled, 2=completed, 3=cancelled, 4=no_show'");

        // 5. fb_tbl_workout_assignment: enum → tinyInt
        DB::statement("ALTER TABLE fb_tbl_workout_assignment ADD COLUMN status_new TINYINT DEFAULT 1 AFTER due_date");
        DB::statement("UPDATE fb_tbl_workout_assignment SET status_new = CASE
            WHEN status = 'pending' THEN 1
            WHEN status = 'in_progress' THEN 2
            WHEN status = 'completed' THEN 3
            ELSE 1 END");
        DB::statement("ALTER TABLE fb_tbl_workout_assignment DROP COLUMN status");
        DB::statement("ALTER TABLE fb_tbl_workout_assignment CHANGE status_new status TINYINT DEFAULT 1 COMMENT '1=pending, 2=in_progress, 3=completed'");

        // 6. fb_tbl_diet_plan_assignment: enum → tinyInt
        DB::statement("ALTER TABLE fb_tbl_diet_plan_assignment ADD COLUMN status_new TINYINT DEFAULT 1 AFTER due_date");
        DB::statement("UPDATE fb_tbl_diet_plan_assignment SET status_new = CASE
            WHEN status = 'pending' THEN 1
            WHEN status = 'in_progress' THEN 2
            WHEN status = 'completed' THEN 3
            ELSE 1 END");
        DB::statement("ALTER TABLE fb_tbl_diet_plan_assignment DROP COLUMN status");
        DB::statement("ALTER TABLE fb_tbl_diet_plan_assignment CHANGE status_new status TINYINT DEFAULT 1 COMMENT '1=pending, 2=in_progress, 3=completed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fb_tbl_trainer MODIFY status ENUM('active','inactive','suspended') DEFAULT 'active'");
        DB::statement("ALTER TABLE fb_tbl_client MODIFY status ENUM('active','inactive') DEFAULT 'active'");
        DB::statement("ALTER TABLE fb_tbl_trainer_client MODIFY status ENUM('active','inactive','completed') DEFAULT 'active'");
        DB::statement("ALTER TABLE fb_tbl_session MODIFY status ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled'");
        DB::statement("ALTER TABLE fb_tbl_workout_assignment MODIFY status ENUM('pending','in_progress','completed') DEFAULT 'pending'");
        DB::statement("ALTER TABLE fb_tbl_diet_plan_assignment MODIFY status ENUM('pending','in_progress','completed') DEFAULT 'pending'");
    }
};

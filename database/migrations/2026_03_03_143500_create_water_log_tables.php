<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two-table water tracking system.
     * ─────────────────────────────────────────────────────────
     * PARENT : fb_tbl_water_daily_log  → one row per user per day
     * CHILD  : fb_tbl_water_intake     → individual intake entries
     *
     * Polymorphic (loggable) so it works for both Client & Trainer.
     */
    public function up(): void
    {
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        //  PARENT — Daily summary (1 row / user / day)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        Schema::create('fb_tbl_water_daily_log', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner: Client OR Trainer
            $table->unsignedBigInteger('loggable_id');
            $table->string('loggable_type');

            $table->date('log_date');
            $table->unsignedInteger('water_goal_ml')->default(2000)->comment('Daily target in ml');
            $table->unsignedInteger('total_consumed_ml')->default(0)->comment('Denormalized running total');

            $table->timestamps();

            // One row per user per day — enforced at DB level
            $table->unique(['loggable_id', 'loggable_type', 'log_date'], 'water_daily_owner_date_unique');
        });

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        //  CHILD — Individual intake entries (N rows / day)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        Schema::create('fb_tbl_water_intake', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('water_daily_log_id');
            $table->foreign('water_daily_log_id')
                  ->references('id')
                  ->on('fb_tbl_water_daily_log')
                  ->onDelete('cascade');

            $table->unsignedInteger('amount_ml')->comment('Intake amount in ml');
            $table->time('logged_at')->comment('Time the intake was recorded');

            $table->timestamps();

            // Index for fast date-ordered listing
            $table->index(['water_daily_log_id', 'logged_at'], 'water_intake_log_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_water_intake');
        Schema::dropIfExists('fb_tbl_water_daily_log');
    }
};

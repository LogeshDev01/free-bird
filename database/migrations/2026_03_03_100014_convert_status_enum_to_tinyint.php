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
        // Consolidated into base migrations
    }

    public function down(): void
    {
        //
    }
};

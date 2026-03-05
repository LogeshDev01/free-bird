<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            // batch_id already exists — only add missing columns

            // ── Top-level weight defaults for the assigned workout ─────────────
            $table->decimal('kg', 8, 2)->nullable()->after('custom_sets')
                  ->comment('Default weight in kg for this assignment');

            // ── Duration in seconds for the full assigned workout ──────────────
            $table->unsignedInteger('duration')->nullable()->after('kg')
                  ->comment('Total workout duration in seconds');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropColumn(['kg', 'duration']);
        });
    }
};

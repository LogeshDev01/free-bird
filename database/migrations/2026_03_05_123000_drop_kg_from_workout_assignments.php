<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the redundant `kg` column from workout_assignment.
     * The kg value lives inside each custom_sets item (JSON), so the
     * top-level column is duplicate data that should never exist.
     *
     * The `duration` column is kept — it is auto-populated at assignment
     * time by summing all custom_sets[*].duration values.
     */
    public function up(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropColumn('kg');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->decimal('kg', 8, 2)->nullable()->after('custom_sets');
        });
    }
};

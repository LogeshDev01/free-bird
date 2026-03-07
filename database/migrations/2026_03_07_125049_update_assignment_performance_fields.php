<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->json('performance_data')->nullable()->after('custom_sets')->comment('Actual data logged by client');
            $table->boolean('is_completed')->default(false)->after('status');
            $table->timestamp('completed_at')->nullable()->after('is_completed');
        });

        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->json('performance_data')->nullable()->after('notes')->comment('Actual data logged by client');
            $table->boolean('is_completed')->default(false)->after('status');
            $table->timestamp('completed_at')->nullable()->after('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->dropColumn(['performance_data', 'is_completed', 'completed_at']);
        });

        Schema::table('fb_tbl_diet_plan_assignment', function (Blueprint $table) {
            $table->dropColumn(['performance_data', 'is_completed', 'completed_at']);
        });
    }
};

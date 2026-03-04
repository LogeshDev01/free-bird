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
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            $table->foreignId('current_subscription_id')->nullable()->constrained('fb_tbl_client_subscriptions')->nullOnDelete();
        });

        Schema::table('fb_tbl_diet_plan_category', function (Blueprint $table) {
            $table->string('minimum_plan_tier')->nullable()->comment('Slug of the minimum plan required');
        });

        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            $table->string('minimum_plan_tier')->nullable()->comment('Slug of the minimum plan required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            $table->dropForeign(['current_subscription_id']);
            $table->dropColumn('current_subscription_id');
        });

        Schema::table('fb_tbl_diet_plan_category', function (Blueprint $table) {
            $table->dropColumn('minimum_plan_tier');
        });

        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            $table->dropColumn('minimum_plan_tier');
        });
    }
};

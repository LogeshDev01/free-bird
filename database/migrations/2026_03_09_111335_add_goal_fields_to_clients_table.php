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
            $table->unsignedInteger('steps_goal')->default(10000)->after('goal');
            $table->decimal('fat_goal', 5, 2)->nullable()->after('steps_goal');
            $table->decimal('bmi_goal', 5, 2)->nullable()->after('fat_goal');
            $table->decimal('ideal_weight_goal', 8, 2)->nullable()->after('bmi_goal');
            $table->unsignedInteger('bmr_goal')->nullable()->after('ideal_weight_goal');
            $table->unsignedInteger('calories_goal')->nullable()->after('bmr_goal');
            $table->timestamp('goals_updated_at')->nullable()->after('calories_goal');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_client', function (Blueprint $table) {
            $table->dropColumn([
                'steps_goal', 
                'fat_goal', 
                'bmi_goal', 
                'ideal_weight_goal', 
                'bmr_goal', 
                'calories_goal',
                'goals_updated_at'
            ]);
        });
    }
};

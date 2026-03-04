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
        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            if (!Schema::hasColumn('fb_tbl_workout_category', 'workout_category_type_id')) {
                $table->unsignedBigInteger('workout_category_type_id')->nullable();
            }
            $table->foreign('workout_category_type_id')->references('id')->on('fb_tbl_workout_category_type')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            $table->dropForeign(['workout_category_type_id']);
            $table->dropColumn('workout_category_type_id');
        });
    }
};

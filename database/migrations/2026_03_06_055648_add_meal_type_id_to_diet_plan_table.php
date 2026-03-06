<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fb_tbl_diet_plan', function (Blueprint $table) {
            // Add the FK column after the old string column
            $table->foreignId('meal_type_id')
                  ->nullable()
                  ->after('meal_type')
                  ->constrained('fb_tbl_meal_type')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_diet_plan', function (Blueprint $table) {
            $table->dropForeign(['meal_type_id']);
            $table->dropColumn('meal_type_id');
        });
    }
};

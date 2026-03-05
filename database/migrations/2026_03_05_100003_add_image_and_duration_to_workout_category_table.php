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
            $table->string('image')->nullable()->after('icon');
            $table->string('duration')->nullable()->after('description'); // e.g. '00:04:00'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_workout_category', function (Blueprint $table) {
            $table->dropColumn(['image', 'duration']);
        });
    }
};

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
        Schema::table('fb_tbl_workout', function (Blueprint $table) {
            $table->decimal('lbs', 8, 2)->nullable()->after('rest_seconds');
            $table->decimal('kg', 8, 2)->nullable()->after('lbs');
            $table->string('weight_unit', 10)->default('kg')->after('kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_workout', function (Blueprint $table) {
            $table->dropColumn(['lbs', 'kg', 'weight_unit']);
        });
    }
};

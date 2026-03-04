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
        Schema::create('fb_tbl_plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('fb_tbl_plans')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('fb_tbl_features')->onDelete('cascade');
            $table->integer('limit')->nullable()->comment('Null for boolean, -1 for unlimited, >0 for quota');
            $table->timestamps();
            
            $table->unique(['plan_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_plan_features');
    }
};

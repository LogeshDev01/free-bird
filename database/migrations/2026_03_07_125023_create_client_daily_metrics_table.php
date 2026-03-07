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
        Schema::create('fb_tbl_client_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->date('log_date');
            
            // Numeric Metrics
            $table->unsignedInteger('steps')->default(0);
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('fat_percent', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->unsignedInteger('bmr')->nullable();
            $table->decimal('ideal_weight', 8, 2)->nullable();
            $table->unsignedInteger('calories_consumed')->default(0);
            $table->unsignedInteger('calories_burnt')->default(0);
            
            // Body Measurements
            $table->decimal('chest_cm', 6, 2)->nullable();
            $table->decimal('waist_cm', 6, 2)->nullable();
            $table->decimal('neck_cm', 6, 2)->nullable();

            $table->timestamps();

            // Ensure one entry per client per day
            $table->unique(['client_id', 'log_date'], 'client_daily_metrics_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_client_daily_metrics');
    }
};

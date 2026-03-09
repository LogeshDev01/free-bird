<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_diet_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('fb_tbl_diet_plan_category')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('fb_tbl_trainer')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('calories')->nullable();
            $table->decimal('protein_grams', 6, 1)->nullable();
            $table->decimal('carbs_grams', 6, 1)->nullable();
            $table->decimal('fat_grams', 6, 1)->nullable();
            $table->string('meal_type')->nullable()->comment('breakfast, lunch, dinner, snack');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('minimum_plan_tier')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_diet_plan');
    }
};

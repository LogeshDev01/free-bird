<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_workout', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('fb_tbl_workout_category')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('fb_tbl_trainer')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('video_url')->nullable();
            $table->string('difficulty')->nullable()->comment('beginner, intermediate, advanced');
            $table->string('muscle_group')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('sets')->nullable();
            $table->integer('reps')->nullable();
            $table->decimal('rest_seconds', 5, 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_workout');
    }
};

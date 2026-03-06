<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_workout_assignment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->unsignedBigInteger('assigned_by_id')->nullable();
            $table->string('assigned_by_type')->nullable();
            $table->string('batch_id')->nullable();
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('fb_tbl_workout_category')->onDelete('cascade');
            $table->foreignId('workout_id')->constrained('fb_tbl_workout')->onDelete('cascade');
            $table->json('custom_sets')->nullable();
            $table->integer('duration')->nullable();
            $table->date('assigned_date');
            $table->date('due_date')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0=draft, 1=pending, 2=in_progress, 3=completed');
            $table->softDeletes();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'assigned_date']);
            $table->index(['trainer_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_workout_assignment');
    }
};

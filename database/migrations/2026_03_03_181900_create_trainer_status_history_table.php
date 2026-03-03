<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trainer Status History — append-only log of every job status change.
     */
    public function up(): void
    {
        Schema::create('fb_tbl_trainer_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->string('status')->comment('New status value: probation, full-time, part-time, terminated, resigned');
            $table->string('title')->comment('Human-readable label, e.g. Full-Time Conversion');
            $table->text('note')->nullable()->comment('Optional details about the change');
            $table->date('effective_date')->comment('When this status took effect');
            $table->timestamps();

            $table->index(['trainer_id', 'effective_date'], 'trainer_status_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_trainer_status_history');
    }
};

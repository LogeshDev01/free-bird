<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fb_tbl_session_review
     * ─────────────────────────────────────────────────
     * One row per session. Both trainer and client fill
     * their side independently after session is completed.
     *
     * Replaces: fb_tbl_trainer_rating
     * (old table is kept intact for backward compatibility;
     *  Trainer model now reads from this table instead.)
     */
    public function up(): void
    {
        Schema::create('fb_tbl_session_review', function (Blueprint $table) {
            $table->id();

            // Linked session (one review record per session)
            $table->foreignId('session_id')
                  ->constrained('fb_tbl_session')
                  ->onDelete('cascade');

            // The two parties
            $table->foreignId('trainer_id')
                  ->constrained('fb_tbl_trainer')
                  ->onDelete('cascade');

            $table->foreignId('client_id')
                  ->constrained('fb_tbl_client')
                  ->onDelete('cascade');

            // ── Client → Trainer ───────────────────────────────
            $table->decimal('client_rating', 2, 1)->nullable()->comment('1.0 to 5.0 — client rates the trainer');
            $table->text('client_review')->nullable()->comment('Client text feedback about the trainer');
            $table->timestamp('client_reviewed_at')->nullable();

            // ── Trainer → Client ───────────────────────────────
            $table->decimal('trainer_rating', 2, 1)->nullable()->comment('1.0 to 5.0 — trainer scores the client performance');
            $table->text('trainer_review')->nullable()->comment('Trainer notes / feedback about the client');
            $table->timestamp('trainer_reviewed_at')->nullable();

            $table->timestamps();

            // One review record per session
            $table->unique('session_id');

            // Fast lookups
            $table->index('trainer_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_session_review');
    }
};

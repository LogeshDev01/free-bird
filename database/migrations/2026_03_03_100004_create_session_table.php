<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->foreignId('slot_id')->nullable()->constrained('fb_tbl_trainer_slot')->onDelete('set null');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=scheduled, 2=completed, 3=cancelled, 4=no_show');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'session_date']);
            $table->index(['client_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_session');
    }
};

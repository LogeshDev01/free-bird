<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_trainer_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->tinyInteger('day_of_week')->comment('0=Sunday, 6=Saturday');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_trainer_slot');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_trainer_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive, 2=completed');
            $table->softDeletes();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['trainer_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_trainer_client');
    }
};

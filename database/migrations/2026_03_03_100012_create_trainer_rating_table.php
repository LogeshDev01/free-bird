<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_trainer_rating', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->decimal('rating', 2, 1)->comment('1.0 to 5.0');
            $table->text('review')->nullable();
            $table->date('month')->nullable()->comment('For monthly satisfaction tracking');
            $table->timestamps();

            $table->index(['trainer_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_trainer_rating');
    }
};

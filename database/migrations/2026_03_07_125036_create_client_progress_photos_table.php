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
        Schema::create('fb_tbl_client_progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->date('log_date');
            
            $table->string('front_view')->nullable();
            $table->string('side_view')->nullable();
            $table->string('back_view')->nullable();

            $table->timestamps();

            // Allow multiple photos per day if needed, but unique per date is better for a standard progress view
            // $table->unique(['client_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_client_progress_photos');
    }
};

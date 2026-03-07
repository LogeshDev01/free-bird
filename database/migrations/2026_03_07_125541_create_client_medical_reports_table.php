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
        Schema::create('fb_tbl_client_medical_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->string('name');
            $table->string('file_path');
            $table->date('report_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_client_medical_reports');
    }
};

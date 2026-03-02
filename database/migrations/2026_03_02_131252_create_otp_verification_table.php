<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_otp_verification', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_number');
            $table->string('otp', 6);
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->string('device')->nullable();
            $table->timestamps();

            $table->index('mobile_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_otp_verification');
    }
};
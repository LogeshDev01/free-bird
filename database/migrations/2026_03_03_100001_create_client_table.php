<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_client', function (Blueprint $table) {
            $table->id();

            // Personal Information
            $table->string('profile_pic')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('dob')->nullable();
            $table->string('phone')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();

            // Physical Details
            $table->decimal('height', 5, 2)->nullable()->comment('in cm');
            $table->decimal('weight', 5, 2)->nullable()->comment('in kg');
            $table->string('goal')->nullable()->comment('e.g. Weight Loss, Muscle Gain');

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_person')->nullable();
            $table->string('emergency_phone')->nullable();

            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_client');
    }
};

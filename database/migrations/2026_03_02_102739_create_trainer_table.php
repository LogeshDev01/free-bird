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
        Schema::create('fb_tbl_trainer', function (Blueprint $table) {
            $table->id();
            
            // Personal Information
            $table->string('profile_pic')->nullable();
            $table->string('qr_code')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender');
            $table->date('dob');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password'); // Added for login
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->string('country');
            
            // Professional Details
            $table->string('specialization');
            $table->string('experience');
            $table->string('qualification')->nullable();
            
            // Employment & Account
            $table->string('emp_id')->unique();
            $table->date('joining_date');
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->string('shift')->nullable();
            $table->string('job_status')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_person')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('bio')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive, 2=suspended');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('fb_tbl_trainer');
        Schema::enableForeignKeyConstraints();
        
    }
};

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
        // 1. States Table
        Schema::create('fb_tbl_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('name');
            $table->string('iso_code', 10)->nullable();
            $table->string('fips_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Cities Table
        Schema::create('fb_tbl_city', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('fb_tbl_state')->onDelete('cascade');
            $table->string('name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Zones Table
        Schema::create('fb_tbl_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('fb_tbl_city')->onDelete('cascade');
            $table->string('name');
            $table->string('zone_code', 50)->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // Link to a User who manages this specific business zone
            $table->json('boundary_coordinates')->nullable(); // Advanced business logic
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_zone');
        Schema::dropIfExists('fb_tbl_city');
        Schema::dropIfExists('fb_tbl_state');
    }
};

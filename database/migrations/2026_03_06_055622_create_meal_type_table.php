<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_meal_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // e.g. Breakfast, Lunch, Dinner, Snack
            $table->string('icon')->nullable();        // optional icon/emoji
            $table->string('description')->nullable(); // optional description
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the standard meal types
        DB::table('fb_tbl_meal_type')->insert([
            ['name' => 'Breakfast', 'icon' => '🌅', 'description' => 'Morning meal',      'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lunch',     'icon' => '☀️',  'description' => 'Midday meal',       'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dinner',    'icon' => '🌙', 'description' => 'Evening meal',      'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Snack',     'icon' => '🍎', 'description' => 'Between-meal bite', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pre-Workout',  'icon' => '⚡', 'description' => 'Fuel before training', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Post-Workout', 'icon' => '💪', 'description' => 'Recovery after training', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_meal_type');
    }
};

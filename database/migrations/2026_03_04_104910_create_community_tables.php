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
        // 1. Community Categories (Master)
        Schema::create('fb_tbl_community_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('show_to_admin')->default(true);
            $table->boolean('show_to_trainer')->default(true);
            $table->boolean('show_to_client')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Community Posts
        Schema::create('fb_tbl_community_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('fb_tbl_community_category')->onDelete('cascade');
            $table->nullableMorphs('author'); // Admin, Trainer, or Client
            $table->enum('target_audience', ['trainer', 'client', 'all'])->default('all');
            $table->string('title');
            $table->text('content');
            $table->string('image')->nullable();
            $table->integer('total_likes')->default(0);
            $table->integer('total_comments')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Community Interactions (Likes)
        Schema::create('fb_tbl_community_like', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('fb_tbl_community_post')->onDelete('cascade');
            $table->morphs('user'); // Trainer or Client
            $table->timestamps();
            
            $table->unique(['post_id', 'user_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_community_like');
        Schema::dropIfExists('fb_tbl_community_post');
        Schema::dropIfExists('fb_tbl_community_category');
    }
};

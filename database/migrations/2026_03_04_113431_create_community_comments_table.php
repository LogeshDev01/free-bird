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
        Schema::create('fb_tbl_community_comment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('fb_tbl_community_post')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('fb_tbl_community_comment')->onDelete('cascade');
            $table->morphs('author'); // Trainer or Client
            $table->text('content');
            $table->integer('total_likes')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_community_comment');
    }
};

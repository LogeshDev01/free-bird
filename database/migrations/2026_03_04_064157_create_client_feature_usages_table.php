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
        Schema::create('fb_tbl_client_feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_subscription_id')->constrained('fb_tbl_client_subscriptions')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('fb_tbl_features')->onDelete('cascade');
            $table->integer('used_count')->default(0);
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
            
            $table->unique(['client_subscription_id', 'feature_id'], 'client_sub_feature_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_client_feature_usages');
    }
};

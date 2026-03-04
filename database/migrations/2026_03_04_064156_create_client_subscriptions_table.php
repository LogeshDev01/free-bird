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
        Schema::create('fb_tbl_client_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('fb_tbl_client')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('fb_tbl_plans')->onDelete('cascade');
            $table->enum('status', ['active', 'canceled', 'past_due', 'expired'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('payment_gateway_ref')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_client_subscriptions');
    }
};

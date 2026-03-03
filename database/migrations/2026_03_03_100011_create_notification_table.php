<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fb_tbl_notification', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable'); // notifiable_id + notifiable_type (Trainer or Client)
            $table->string('title');
            $table->text('message');
            $table->string('type')->nullable()->comment('session, workout, diet, system');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_id', 'notifiable_type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_notification');
    }
};

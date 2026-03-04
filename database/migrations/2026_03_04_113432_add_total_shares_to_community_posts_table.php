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
        Schema::table('fb_tbl_community_post', function (Blueprint $table) {
            $table->integer('total_shares')->default(0)->after('total_comments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_community_post', function (Blueprint $table) {
            $table->dropColumn('total_shares');
        });
    }
};

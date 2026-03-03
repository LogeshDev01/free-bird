<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add missing columns to fb_tbl_trainer.
     */
    public function up(): void
    {
        Schema::table('fb_tbl_trainer', function (Blueprint $table) {
            if (!Schema::hasColumn('fb_tbl_trainer', 'status')) {
                $table->tinyInteger('status')->default(1)->after('job_status')->comment('1=Active, 0=Inactive, 2=Suspended');
            }
            if (!Schema::hasColumn('fb_tbl_trainer', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('profile_pic');
            }
            if (!Schema::hasColumn('fb_tbl_trainer', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fb_tbl_trainer', function (Blueprint $table) {
            $table->dropColumn(['status', 'qr_code']);
            $table->dropSoftDeletes();
        });
    }
};

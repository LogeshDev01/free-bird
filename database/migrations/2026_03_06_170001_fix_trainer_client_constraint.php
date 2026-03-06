<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix fb_tbl_trainer_client:
     *  1. Drop the overly strict UNIQUE(trainer_id, client_id) constraint
     *     — This blocked re-assigning a client to the same trainer after they left.
     *  2. Add soft deletes (deleted_at) so old assignments are preserved as history.
     *  3. Add a new partial-style unique index using deleted_at to allow
     *     re-assignment while keeping past rows intact.
     */
    public function up(): void
    {
        // ── Step 1: Add a plain (non-unique) index FIRST ──────────────────────
        // MySQL requires at least one index backing any FK constraint.
        // The unique index 'trainer_client_enrollment_unique' currently serves
        // as that backing. We must add a plain index before we can safely drop
        // the unique one — otherwise MySQL raises error 1553.
        $hasPlainIndex = count(\DB::select("
            SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = 'fb_tbl_trainer_client'
              AND NON_UNIQUE   = 1
              AND INDEX_NAME   = 'tc_trainer_client_idx'
        ", [config('database.connections.mysql.database')])) > 0;

        if (!$hasPlainIndex) {
            \DB::statement("ALTER TABLE `fb_tbl_trainer_client`
                ADD INDEX `tc_trainer_client_idx` (`trainer_id`, `client_id`)");
        }

        // ── Step 2: Now safely drop the unique constraint ─────────────────────
        $db      = config('database.connections.mysql.database');
        $indexes = \DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = 'fb_tbl_trainer_client'
              AND NON_UNIQUE   = 0
              AND INDEX_NAME  != 'PRIMARY'
        ", [$db]);

        foreach ($indexes as $index) {
            \DB::statement("ALTER TABLE `fb_tbl_trainer_client` DROP INDEX `{$index->INDEX_NAME}`");
        }

        // ── Step 3: Add soft deletes only if column doesn't already exist ─────
        if (!Schema::hasColumn('fb_tbl_trainer_client', 'deleted_at')) {
            Schema::table('fb_tbl_trainer_client', function (Blueprint $table) {
                $table->softDeletes()->after('end_date');
            });
        }
    }

    public function down(): void
    {
        Schema::table('fb_tbl_trainer_client', function (Blueprint $table) {
            // Drop the plain index we added
            $table->dropIndex('tc_trainer_client_idx');

            if (Schema::hasColumn('fb_tbl_trainer_client', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            // Restore the original unique constraint
            $table->unique(['trainer_id', 'client_id'], 'trainer_client_enrollment_unique');
        });
    }


};

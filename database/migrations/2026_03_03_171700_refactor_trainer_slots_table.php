<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update trainer slots to match new UI requirements.
     * 1. Create Slot Types master table.
     * 2. Populate default slot types.
     * 3. Refactor Trainer Slots table with date-based bookings.
     */
    public function up(): void
    {
        // ── 1. Create Slot Types Master ──────────────────────────
        Schema::create('fb_tbl_slot_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();
        });

        // ── 2. Populate Initial Data ─────────────────────────────
        DB::table('fb_tbl_slot_type')->insert([
            ['name' => 'Personal Training', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Group Session', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Yoga', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'HIIT', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── 3. Update Trainer Slots ──────────────────────────────
        Schema::table('fb_tbl_trainer_slot', function (Blueprint $table) {
            // Drop old logic
            if (Schema::hasColumn('fb_tbl_trainer_slot', 'day_of_week')) {
                $table->dropColumn('day_of_week');
            }
            if (Schema::hasColumn('fb_tbl_trainer_slot', 'is_available')) {
                $table->dropColumn('is_available');
            }
            if (Schema::hasColumn('fb_tbl_trainer_slot', 'location')) {
                $table->dropColumn('location');
            }

            // Add new UI fields
            $table->date('date')->after('trainer_id');
            $table->foreignId('slot_type_id')->nullable()->after('date')->constrained('fb_tbl_slot_type')->nullOnDelete();
            $table->text('note')->nullable()->after('end_time');

            // Update indexes
            $table->index(['trainer_id', 'date'], 'trainer_slot_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fb_tbl_trainer_slot', function (Blueprint $table) {
            $table->dropForeign(['slot_type_id']);
            $table->dropColumn(['date', 'slot_type_id', 'note']);
            $table->dropIndex('trainer_slot_date_idx');

            // Restore old
            $table->tinyInteger('day_of_week')->after('trainer_id')->default(1);
            $table->boolean('is_available')->default(true);
            $table->string('location')->nullable();
        });

        Schema::dropIfExists('fb_tbl_slot_type');
    }
};

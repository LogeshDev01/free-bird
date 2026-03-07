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
        // 1. Leave Types Master
        Schema::create('fb_tbl_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default leave types
        DB::table('fb_tbl_leave_types')->insert([
            ['name' => 'Permission', 'description' => 'Temporary time off for short personal requirements.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Paid Leave', 'description' => 'Standard Annual Leave Balance.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Unpaid Leave', 'description' => 'For Extended Time Off Without Pay.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sick Leave', 'description' => 'Immediate Off For Urgent Matters.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Trainer Leaves Table
        Schema::create('fb_tbl_trainer_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('fb_tbl_trainer')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('fb_tbl_leave_types')->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason')->nullable();
            $table->text('additional_note')->default(false);
            $table->tinyInteger('status')->default(0)->comment('0=Pending, 1=Approved, 2=Rejected');
            $table->text('admin_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['trainer_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_tbl_trainer_leaves');
        Schema::dropIfExists('fb_tbl_leave_types');
    }
};

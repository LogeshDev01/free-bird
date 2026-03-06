<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trainer;
use App\Models\Client;
use App\Models\SlotType;
use App\Models\TrainerSlot;
use App\Models\Session;
use App\Models\WorkoutCategoryType;
use App\Models\WorkoutCategory;
use App\Models\Workout;
use App\Models\WorkoutAssignment;
use App\Models\DietPlanCategory;
use App\Models\DietPlan;
use App\Models\DietPlanAssignment;
use App\Models\MealType;
use App\Models\Notification;
use App\Models\TrainerRating;
use App\Models\City;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── 1. Static Master Data ──────────────────────────
        $this->seedMasterData();

        // Location Data (Canada)
        $toronto = City::where('name', 'Toronto')->first();
        $calgary = City::where('name', 'Calgary')->first();
        $vancouver = City::where('name', 'Vancouver')->first();
        
        $torontoZone = Zone::where('city_id', $toronto->id)->first();
        $calgaryZone = Zone::where('city_id', $calgary->id)->first();

        $trainer = Trainer::updateOrCreate(
            ['emp_id' => 'TRN0001'],
            [
                'phone'          => '9876543210',
                'password'       => Hash::make('123456'), // Ensure we can login
                'first_name'     => 'Logesh',
                'last_name'      => 'Dev',
                'gender'         => 'Male',
                'dob'            => '1995-01-01',
                'email'          => 'logesh@example.com',
                'address'        => '123 Tech Park',
                'city_id'        => $toronto->id,
                'state_id'       => $toronto->state_id,
                'zone_id'        => $torontoZone->id,
                'zip_code'       => 'M5H 2N2',
                'country'        => 'Canada',
                'specialization' => 'Strength & Conditioning',
                'experience'     => '5 Years',
                'qualification'  => 'NSCA Certified Personal Trainer',
                'joining_date'   => '2024-01-01',
                'status'         => Trainer::STATUS_ACTIVE,
            ]
        );

        // ── 3. Clients ─────────────────────────────────────
        $client1 = Client::updateOrCreate(
            ['phone' => '1234567890'],
            [
                'first_name' => 'Scarlett',
                'last_name'  => 'Johansson',
                'gender'     => 'Female',
                'dob'        => '1998-05-15',
                'email'      => 'scarlett@example.com',
                'password'   => Hash::make('password'),
                'address'    => '456 Hill Road',
                'zone'       => 'Downtown',
                'city_id'    => $toronto->id,
                'state_id'   => $toronto->state_id,
                'zone_id'    => $torontoZone->id,
                'zip_code'   => 'M5H 2N1',
                'country'    => 'Canada',
                'goal'       => 'Weight Loss & Toning',
                'status'     => Client::STATUS_ACTIVE,
            ]
        );

        $client2 = Client::updateOrCreate(
            ['phone' => '0987654321'],
            [
                'first_name' => 'Carter',
                'last_name'  => 'Wilson',
                'gender'     => 'Male',
                'dob'        => '1999-10-20',
                'email'      => 'carter@example.com',
                'password'   => Hash::make('password'),
                'address'    => '789 Lake View',
                'zone'       => 'Beltline',
                'city_id'    => $calgary->id,
                'state_id'   => $calgary->state_id,
                'zone_id'    => $calgaryZone->id,
                'zip_code'   => 'T2P 2M5',
                'country'    => 'Canada',
                'goal'       => 'Upper Body Strength Boost',
                'status'     => Client::STATUS_ACTIVE,
            ]
        );

        // Assign to trainer
        if (!$trainer->clients()->where('client_id', $client1->id)->exists()) {
            $trainer->clients()->attach($client1->id, ['status' => Trainer::CLIENT_ACTIVE, 'start_date' => now()]);
        }
        if (!$trainer->clients()->where('client_id', $client2->id)->exists()) {
            $trainer->clients()->attach($client2->id, ['status' => Trainer::CLIENT_ACTIVE, 'start_date' => now()]);
        }

        // ── 4. Slots for Today ─────────────────────────────
        $today = Carbon::today();
        $ptType = SlotType::where('name', 'Personal Training')->first();

        $slot1 = TrainerSlot::updateOrCreate(
            ['trainer_id' => $trainer->id, 'date' => $today, 'start_time' => '10:00:00'],
            ['end_time' => '11:00:00', 'slot_type_id' => $ptType->id ?? 1]
        );

        $slot2 = TrainerSlot::updateOrCreate(
            ['trainer_id' => $trainer->id, 'date' => $today, 'start_time' => '11:30:00'],
            ['end_time' => '12:30:00', 'slot_type_id' => $ptType->id ?? 1]
        );

        // ── 5. Sessions ────────────────────────────────────
        Session::updateOrCreate(
            ['trainer_id' => $trainer->id, 'client_id' => $client1->id, 'session_date' => $today, 'start_time' => '07:30:00'],
            ['end_time' => '08:15:00', 'slot_id' => $slot1->id, 'status' => Session::STATUS_SCHEDULED, 'location' => $toronto->id]
        );

        Session::updateOrCreate(
            ['trainer_id' => $trainer->id, 'client_id' => $client2->id, 'session_date' => $today, 'start_time' => '10:00:00'],
            ['end_time' => '14:20:00', 'slot_id' => $slot2->id, 'status' => Session::STATUS_SCHEDULED, 'location' => $vancouver->id]
        );

        // ── 6. Workouts & Diet Plans ───────────────────────
        $this->seedWorkoutsAndDiets($trainer, $client1, $client2);

        // ── 7. Notifications ───────────────────────────────
        Notification::updateOrCreate(
            ['notifiable_id' => $trainer->id, 'notifiable_type' => Trainer::class, 'title' => 'New Session Booked'],
            [
                'message' => 'Scarlett has booked a session for today at 07:30 AM.',
                'type'    => 'session_booking',
            ]
        );

        // ── 8. Ratings ─────────────────────────────────────
        TrainerRating::updateOrCreate(
            ['trainer_id' => $trainer->id, 'client_id' => $client1->id],
            ['rating' => 5, 'review' => 'Great trainer!']
        );

        // ── 9. Leaves ──────────────────────────────────────
        $sickLeave = \App\Models\LeaveType::where('name', 'Sick Leave')->first();
        \App\Models\TrainerLeave::updateOrCreate(
            ['trainer_id' => $trainer->id, 'start_date' => Carbon::today()->addDays(5)->toDateString()],
            [
                'leave_type_id'   => $sickLeave->id,
                'end_date'        => Carbon::today()->addDays(6)->toDateString(),
                'total_days'      => 2,
                'reason'          => 'Flu symptoms',
                'status'          => \App\Models\TrainerLeave::STATUS_APPROVED,
                'additional_note' => 'Will be back soon',
            ]
        );

        \App\Models\TrainerLeave::updateOrCreate(
            ['trainer_id' => $trainer->id, 'start_date' => Carbon::today()->addDays(15)->toDateString()],
            [
                'leave_type_id'   => $sickLeave->id,
                'end_date'        => Carbon::today()->addDays(16)->toDateString(),
                'total_days'      => 2,
                'reason'          => 'Family event',
                'status'          => \App\Models\TrainerLeave::STATUS_PENDING,
            ]
        );
    }

    private function seedMasterData()
    {
        // Slot Types
        $slotTypes = ['Personal Training', 'Group Session', 'Yoga', 'HIIT'];
        foreach ($slotTypes as $name) {
            SlotType::firstOrCreate(['name' => $name]);
        }

        // Workout Category Types
        $wct = WorkoutCategoryType::firstOrCreate(['name' => 'Muscle Building'], ['is_active' => true]);

        // Workout Categories
        WorkoutCategory::firstOrCreate(
            ['name' => 'Upper Body'],
            [
                'workout_category_type_id' => $wct->id,
                'is_active' => true,
                'duration'  => '00:03:30',
                'image'     => null
            ]
        );
        
        WorkoutCategory::firstOrCreate(
            ['name' => 'HIIT Cardio Blast'],
            [
                'workout_category_type_id' => $wct->id,
                'is_active' => true,
                'duration'  => '00:04:00',
                'image'     => null
            ]
        );

        WorkoutCategory::firstOrCreate(
            ['name' => 'Beginner Starter'],
            [
                'workout_category_type_id' => $wct->id,
                'is_active' => true,
                'duration'  => '00:02:30',
                'image'     => null
            ]
        );

        // Diet Plan Categories
        DietPlanCategory::firstOrCreate(
            ['name' => 'Weight Gain'],
            ['is_active' => true]
        );
    }

    private function seedWorkoutsAndDiets($trainer, $client1, $client2)
    {
        $workoutCat = WorkoutCategory::where('name', 'Upper Body')->first();
        $dietCat = DietPlanCategory::where('name', 'Weight Gain')->first();

        // Workout
        $workout = Workout::firstOrCreate(
            ['name' => 'Bench Press', 'trainer_id' => $trainer->id],
            [
                'category_id' => $workoutCat->id,
                'description' => 'Standard barbell bench press',
                'difficulty'  => 'Intermediate',
                'sets'        => 4,
                'reps'        => 12
            ]
        );

        // Diet Plan
        $breakfastType = MealType::where('name', 'Breakfast')->first();

        $diet = DietPlan::firstOrCreate(
            ['name' => 'High Protein Breakfast', 'trainer_id' => $trainer->id],
            [
                'category_id'   => $dietCat->id,
                'calories'      => 800,
                'protein_grams' => 40,
                'meal_type'     => 'Breakfast',           // legacy string
                'meal_type_id'  => $breakfastType?->id,  // FK relation
            ]
        );

        // Assignments
        WorkoutAssignment::firstOrCreate(
            ['client_id' => $client1->id, 'workout_id' => $workout->id],
            [
                'trainer_id'       => $trainer->id,
                'assigned_by_id'   => $trainer->id,
                'assigned_by_type' => Trainer::class,
                'category_id'      => $workoutCat->id,
                'assigned_date'    => now(),
                'status'           => WorkoutAssignment::STATUS_PENDING
            ]
        );

        DietPlanAssignment::firstOrCreate(
            ['client_id' => $client1->id, 'diet_plan_id' => $diet->id],
            [
                'trainer_id'    => $trainer->id,
                'assigned_date' => now(),
                'status'        => DietPlanAssignment::STATUS_PENDING
            ]
        );
    }
}

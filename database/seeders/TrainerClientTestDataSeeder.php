<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trainer;
use App\Models\Client;
use App\Models\ClientDailyMetric;
use App\Models\ClientProgressPhoto;
use App\Models\ClientMedicalReport;
use App\Models\WaterDailyLog;
use App\Models\WorkoutAssignment;
use App\Models\DietPlanAssignment;
use App\Models\Workout;
use App\Models\DietPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TrainerClientTestDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Trainer
        $trainer = Trainer::firstOrCreate(
            ['phone' => '9876543210'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'trainer@example.com',
                'password' => Hash::make('password'),
                'status' => Trainer::STATUS_ACTIVE,
            ]
        );

        // 2. Client
        $client = Client::firstOrCreate(
            ['phone' => '1234567890'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'client@example.com',
                'password' => Hash::make('password'),
                'status' => Client::STATUS_ACTIVE,
                'weight' => 70.5,
                'height' => 170,
                'goal' => 'Weight Loss',
                'dob' => '1995-05-15',
            ]
        );

        // Attach client to trainer if not already
        if (!$trainer->clients()->where('client_id', $client->id)->exists()) {
            $trainer->clients()->attach($client->id, [
                'status' => Trainer::CLIENT_ACTIVE,
                'start_date' => Carbon::now(),
            ]);
        }

        // Create dummy images in storage
        $this->createDummyImages();

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 3. Daily Metrics (at least 5)
        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::today()->subDays($i);
            ClientDailyMetric::updateOrCreate(
                ['client_id' => $client->id, 'log_date' => $date],
                [
                    'steps' => 8000 + ($i * 500),
                    'weight_kg' => 70.5 - ($i * 0.2),
                    'bmi' => 24.5 - ($i * 0.1),
                    'fat_percent' => 20 - ($i * 0.1),
                    'chest_cm' => 95 - ($i * 0.1),
                    'waist_cm' => 85 - ($i * 0.1),
                    'neck_cm' => 38 - ($i * 0.1),
                ]
            );
        }

        // 4. Progress Photos (at least 5)
        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::today()->subDays($i);
            ClientProgressPhoto::updateOrCreate(
                ['client_id' => $client->id, 'log_date' => $date],
                [
                    'front_view' => 'progress/front_' . $i . '.jpg',
                    'side_view' => 'progress/side_' . $i . '.jpg',
                    'back_view' => 'progress/back_' . $i . '.jpg',
                ]
            );
        }

        // 5. Medical Reports (at least 5)
        for ($i = 0; $i < 5; $i++) {
            ClientMedicalReport::updateOrCreate(
                ['client_id' => $client->id, 'name' => 'Blood Test Report ' . ($i + 1)],
                [
                    'file_path' => 'reports/medical_' . $i . '.pdf',
                    'report_date' => Carbon::today()->subMonths($i),
                ]
            );
        }

        // 6. Water Daily Logs (at least 5)
        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::today()->subDays($i);
            WaterDailyLog::updateOrCreate(
                ['loggable_id' => $client->id, 'loggable_type' => Client::class, 'log_date' => $date],
                [
                    'water_goal_ml' => 3000,
                    'total_consumed_ml' => 2000 + ($i * 200),
                ]
            );
        }

        // 7. Workout Assignments (Today and Yesterday)
        $workouts = Workout::limit(5)->get();
        if ($workouts->count() > 0) {
            foreach ([$today, $yesterday] as $index => $date) {
                WorkoutAssignment::updateOrCreate(
                    [
                        'client_id' => $client->id, 
                        'assigned_date' => $date,
                        'workout_id' => $workouts[$index % $workouts->count()]->id
                    ],
                    [
                        'category_id' => $workouts[$index % $workouts->count()]->category_id,
                        'trainer_id' => $trainer->id,
                        'assigned_by_id' => $trainer->id,
                        'assigned_by_type' => Trainer::class,
                        'duration' => 3600,
                        'is_completed' => $index == 0 ? false : true,
                        'status' => $index == 0 ? WorkoutAssignment::STATUS_PENDING : WorkoutAssignment::STATUS_COMPLETED,
                    ]
                );
            }
        }

        // 8. Diet Plan Assignments (Today and Yesterday)
        $dietPlans = DietPlan::limit(5)->get();
        if ($dietPlans->count() > 0) {
            foreach ([$today, $yesterday] as $index => $date) {
                DietPlanAssignment::updateOrCreate(
                    [
                        'client_id' => $client->id, 
                        'assigned_date' => $date,
                        'diet_plan_id' => $dietPlans[$index % $dietPlans->count()]->id
                    ],
                    [
                        'trainer_id' => $trainer->id,
                        'assigned_by_id' => $trainer->id,
                        'assigned_by_type' => Trainer::class,
                        'is_completed' => $index == 0 ? false : true,
                        'status' => $index == 0 ? DietPlanAssignment::STATUS_PENDING : DietPlanAssignment::STATUS_COMPLETED,
                    ]
                );
            }
        }
    }

    private function createDummyImages()
    {
        // Use placeholders or just create empty files if needed, 
        // but it's better to ensure directories exist.
        $directories = ['public/progress', 'public/reports', 'public/workouts', 'public/diets', 'public/clients'];
        foreach ($directories as $dir) {
            if (!Storage::exists($dir)) {
                Storage::makeDirectory($dir);
            }
        }

        // Create some placeholder files if they don't exist
        for ($i = 0; $i < 5; $i++) {
            Storage::put('public/progress/front_' . $i . '.jpg', 'dummy content');
            Storage::put('public/progress/side_' . $i . '.jpg', 'dummy content');
            Storage::put('public/progress/back_' . $i . '.jpg', 'dummy content');
            Storage::put('public/reports/medical_' . $i . '.pdf', 'dummy content');
        }
    }
}

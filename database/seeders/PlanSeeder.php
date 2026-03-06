<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;
use App\Models\Feature;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Features
        $features = [
            ['name' => 'Ad-free Experience', 'slug' => 'ad-free', 'description' => 'No intrusive advertisements'],
            ['name' => 'Custom Workouts', 'slug' => 'custom-workouts', 'description' => 'Create and save your own workout routines'],
            ['name' => 'Nutrition Tracking', 'slug' => 'nutrition-tracking', 'description' => 'Log meals and track macros'],
            ['name' => 'Priority Support', 'slug' => 'priority-support', 'description' => 'Faster response times from our team'],
        ];

        foreach ($features as $f) {
            DB::table('fb_tbl_features')->updateOrInsert(['slug' => $f['slug']], array_merge($f, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 2. Plans
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 0.00,
                'billing_cycle' => 'monthly',
                'features' => ['ad-free']
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 19.99,
                'billing_cycle' => 'monthly',
                'features' => ['ad-free', 'custom-workouts', 'nutrition-tracking']
            ],
            [
                'name' => 'Elite',
                'slug' => 'elite',
                'price' => 199.99,
                'billing_cycle' => 'yearly',
                'features' => ['ad-free', 'custom-workouts', 'nutrition-tracking', 'priority-support']
            ],
        ];

        foreach ($plans as $p) {
            $planFeatures = $p['features'];
            unset($p['features']);
            
            $planId = DB::table('fb_tbl_plans')->updateOrInsert(
                ['slug' => $p['slug']], 
                array_merge($p, ['created_at' => now(), 'updated_at' => now()])
            );

            // Re-fetch to get ID because updateOrInsert doesn't return it
            $plan = DB::table('fb_tbl_plans')->where('slug', $p['slug'])->first();

            foreach ($planFeatures as $fs) {
                $feature = DB::table('fb_tbl_features')->where('slug', $fs)->first();
                DB::table('fb_tbl_plan_features')->updateOrInsert(
                    ['plan_id' => $plan->id, 'feature_id' => $feature->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}

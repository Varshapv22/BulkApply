<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    private const PLANS = [
        ['name' => '1 Month', 'price' => 499, 'duration_days' => 30],
        ['name' => '3 Month', 'price' => 1299, 'duration_days' => 90],
        ['name' => '9 Month', 'price' => 2999, 'duration_days' => 270],
    ];

    public function run(): void
    {
        foreach (self::PLANS as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                [
                    'price' => $plan['price'],
                    'duration_days' => $plan['duration_days'],
                    'is_active' => true,
                    'is_trial_plan' => false,
                ]
            );
        }
    }
}

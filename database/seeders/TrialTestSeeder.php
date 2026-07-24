<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TrialTestSeeder extends Seeder
{
    public function run(): void
    {
        // --- Plans ---
        Plan::firstOrCreate(['name' => 'Free'], ['price' => 0, 'duration_days' => 7, 'is_active' => true]);
        Plan::firstOrCreate(['name' => '1 Month'], ['price' => 9.99, 'duration_days' => 30, 'is_active' => true]);
        $threeMonth = Plan::firstOrCreate(['name' => '3 Month'], ['price' => 24.99, 'duration_days' => 90, 'is_active' => true]);
        Plan::firstOrCreate(['name' => '9 Month'], ['price' => 69.99, 'duration_days' => 270, 'is_active' => true]);

        // --- Users ---

        // 1. Trial active (3 days left)
        User::firstOrCreate(['email' => 'trial-active@test.com'], [
            'name'          => 'Trial Active User',
            'password'      => Hash::make('password'),
            'trial_ends_at' => now()->addDays(3),
            'is_active'     => true,
        ]);

        // 2. Trial expired, no plan
        User::firstOrCreate(['email' => 'trial-expired@test.com'], [
            'name'          => 'Trial Expired User',
            'password'      => Hash::make('password'),
            'trial_ends_at' => now()->subDays(2),
            'is_active'     => true,
        ]);

        // 3. Trial expired but has a plan (no modal)
        $subscribedUser = User::firstOrCreate(['email' => 'subscribed@test.com'], [
            'name'          => 'Subscribed User',
            'password'      => Hash::make('password'),
            'trial_ends_at' => now()->subDays(5),
            'is_active'     => true,
        ]);
        if (! $subscribedUser->activeSubscription()) {
            Subscription::create([
                'user_id'    => $subscribedUser->id,
                'plan_id'    => $threeMonth->id,
                'status'     => Subscription::STATUS_ACTIVE,
                'starts_at'  => now()->subDays(5),
                'ends_at'    => now()->subDays(5)->addDays($threeMonth->duration_days),
            ]);
        }

        // 4. Suspended account
        User::firstOrCreate(['email' => 'suspended@test.com'], [
            'name'          => 'Suspended User',
            'password'      => Hash::make('password'),
            'trial_ends_at' => now()->addDays(7),
            'is_active'     => false,
        ]);

        $this->command->info('Trial test users created:');
        $this->command->table(
            ['Email', 'Password', 'Scenario'],
            [
                ['trial-active@test.com',   'password', 'Trial active (3 days left) — normal access'],
                ['trial-expired@test.com',  'password', 'Trial expired — upgrade modal shown'],
                ['subscribed@test.com',     'password', 'Has 3 Month plan — no modal'],
                ['suspended@test.com',      'password', 'Suspended account — suspended modal shown'],
            ]
        );
    }
}

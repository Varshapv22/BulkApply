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
        $basic = Plan::firstOrCreate(['name' => 'Basic'], [
            'price'                    => 9.99,
            'billing_interval'         => 'monthly',
            'email_limit'              => 100,
            'resume_limit'             => 3,
            'daily_application_limit'  => 20,
            'queue_priority'           => 1,
            'chrome_extension_access'  => true,
            'ats_checker_access'       => false,
            'api_access'               => false,
            'is_active'                => true,
        ]);

        $pro = Plan::firstOrCreate(['name' => 'Pro'], [
            'price'                    => 24.99,
            'billing_interval'         => 'monthly',
            'email_limit'              => 500,
            'resume_limit'             => 10,
            'daily_application_limit'  => 100,
            'queue_priority'           => 5,
            'chrome_extension_access'  => true,
            'ats_checker_access'       => true,
            'api_access'               => true,
            'is_active'                => true,
        ]);

        Plan::firstOrCreate(['name' => 'Enterprise'], [
            'price'                    => 79.99,
            'billing_interval'         => 'monthly',
            'email_limit'              => null,
            'resume_limit'             => null,
            'daily_application_limit'  => null,
            'queue_priority'           => 10,
            'chrome_extension_access'  => true,
            'ats_checker_access'       => true,
            'api_access'               => true,
            'is_active'                => true,
        ]);

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
                'plan_id'    => $pro->id,
                'status'     => Subscription::STATUS_ACTIVE,
                'starts_at'  => now()->subDays(5),
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
                ['subscribed@test.com',     'password', 'Has Pro plan — no modal'],
                ['suspended@test.com',      'password', 'Suspended account — suspended modal shown'],
            ]
        );
    }
}

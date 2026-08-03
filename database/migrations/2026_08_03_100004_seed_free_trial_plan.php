<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('plans')->where('name', 'Free')->orWhere('is_trial_plan', true)->first();

        $attributes = [
            'name' => 'Free Trial',
            'price' => 0,
            'duration_days' => 7,
            'resume_limit' => 2,
            'cover_letter_limit' => 2,
            'is_active' => true,
            'is_trial_plan' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('plans')->where('id', $existing->id)->update($attributes);
        } else {
            DB::table('plans')->insert($attributes + ['created_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('plans')->where('is_trial_plan', true)->update(['is_trial_plan' => false]);
    }
};

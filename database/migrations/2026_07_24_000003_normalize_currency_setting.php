<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('settings')->where('key', 'currency')->first();

        if ($setting) {
            $code = str_contains(strtoupper((string) $setting->value), 'DOLLAR') || strtoupper((string) $setting->value) === 'USD'
                ? 'USD'
                : 'INR';

            DB::table('settings')->where('key', 'currency')->update(['value' => $code]);
        }
    }

    public function down(): void
    {
        // Canonical codes are a superset-compatible improvement — nothing to revert.
    }
};

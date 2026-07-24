<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SETTINGS = [
        ['upi_id', 'billing', 'UPI ID for Payments', '', 'string'],
        ['upi_payee_name', 'billing', 'UPI Payee Name', 'BulkApply', 'string'],
    ];

    public function up(): void
    {
        foreach (self::SETTINGS as [$key, $group, $label, $default, $type]) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'group' => $group,
                'label' => $label,
                'value' => $default,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_column(self::SETTINGS, 0))->delete();
    }
};

<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    private const SETTINGS = [
        // key, group, label, default value, type
        ['site_name', 'general', 'Site Name', 'BulkApply', 'string'],
        ['timezone', 'general', 'Timezone', 'UTC', 'string'],
        ['date_format', 'general', 'Date Format', 'Y-m-d', 'string'],
        ['currency', 'general', 'Currency', 'USD', 'string'],
        ['registration_enabled', 'auth', 'Allow New Registrations', '1', 'boolean'],
        ['email_verification_required', 'auth', 'Require Email Verification', '0', 'boolean'],
        ['password_min_length', 'auth', 'Minimum Password Length', '8', 'integer'],
        ['session_timeout_minutes', 'auth', 'Session Timeout (minutes)', '120', 'integer'],
        ['max_resume_size_mb', 'uploads', 'Max Resume/Cover Letter Size (MB)', '10', 'integer'],
        ['allowed_resume_types', 'uploads', 'Allowed File Types (comma-separated)', 'pdf,doc,docx', 'string'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::SETTINGS as [$key, $group, $label, $default, $type]) {
            Setting::firstOrCreate(['key' => $key], ['group' => $group, 'label' => $label, 'value' => $default, 'type' => $type]);
        }
    }
}

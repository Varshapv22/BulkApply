<?php

namespace Database\Seeders;

use App\Models\ApiConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiConfigSeeder extends Seeder
{
    private const CONFIGS = [
        ['key' => 'adzuna_app_id', 'label' => 'Adzuna App ID', 'description' => 'Overrides ADZUNA_APP_ID from .env if set. Leave blank to use the .env value.'],
        ['key' => 'adzuna_app_key', 'label' => 'Adzuna App Key', 'description' => 'Overrides ADZUNA_APP_KEY from .env if set. Leave blank to use the .env value.'],
        ['key' => 'adzuna_country', 'label' => 'Adzuna Country Code', 'description' => 'Overrides ADZUNA_COUNTRY from .env if set (e.g. in, us, gb).'],
        ['key' => 'google_oauth_client_id', 'label' => 'Google OAuth Client ID', 'description' => 'Not yet used by any feature — reserved for a future Google sign-in integration.'],
        ['key' => 'google_oauth_client_secret', 'label' => 'Google OAuth Client Secret', 'description' => 'Not yet used by any feature — reserved for a future Google sign-in integration.'],
        ['key' => 'openai_api_key', 'label' => 'OpenAI API Key', 'description' => 'Not yet used by any feature — reserved for future AI-assisted features.'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CONFIGS as $config) {
            ApiConfig::firstOrCreate(['key' => $config['key']], $config + ['value' => null, 'active' => true]);
        }
    }
}

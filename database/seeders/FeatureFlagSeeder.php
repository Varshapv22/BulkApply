<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    private const SOURCES = [
        'source.adzuna' => 'Adzuna (aggregated web search)',
        'source.jsearch' => 'JSearch (UAE, Gulf & rest-of-Asia web search)',
        'source.infopark' => 'Infopark',
        'source.technopark' => 'Technopark',
        'source.cyberpark' => 'Cyberpark',
        'source.kinfra' => 'KINFRA Hi-Tech Park Kozhikode',
        'source.smartcity_kozhikode' => 'Smart City Kozhikode',
        'source.malabar_business_center' => 'Malabar Business Center',
        'source.linkedin' => 'LinkedIn (extension)',
        'source.indeed' => 'Indeed (extension)',
        'source.naukri' => 'Naukri (extension)',
        'source.glassdoor' => 'Glassdoor (extension)',
        'source.greenhouse' => 'Greenhouse (extension)',
        'source.lever' => 'Lever (extension)',
    ];

    private const FEATURES = [
        'feature.ats_checker' => 'Resume ATS Checker',
        'feature.job_search' => 'Job Search page',
        'feature.company_insights' => 'Company insights (employee LinkedIn profiles & reviews)',
        'feature.chrome_extension' => 'Chrome Extension API',
        'feature.resume_parser' => 'Resume auto-parse on upload',
        'feature.email_tracking' => 'Email open/click tracking',
        'feature.followups' => 'Follow-up emails',
        'feature.webhooks' => 'Webhook notifications',
        'feature.saved_search_alerts' => 'Saved search alerts',
        'feature.gmail_sync' => 'Automatic Gmail sync',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priority = 0;
        foreach (self::SOURCES as $key => $label) {
            FeatureFlag::firstOrCreate(['key' => $key], ['label' => $label, 'enabled' => true, 'priority' => $priority++]);
        }

        foreach (self::FEATURES as $key => $label) {
            FeatureFlag::firstOrCreate(['key' => $key], ['label' => $label, 'enabled' => true]);
        }
    }
}

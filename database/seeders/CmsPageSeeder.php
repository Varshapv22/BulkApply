<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    private const PAGES = [
        'home' => 'Home',
        'about' => 'About',
        'pricing' => 'Pricing',
        'features' => 'Features',
        'faq' => 'FAQ',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
        'contact' => 'Contact',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PAGES as $slug => $title) {
            CmsPage::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => "This is the {$title} page. Edit this content from the admin CMS section.",
                'status' => 'draft',
            ]);
        }
    }
}

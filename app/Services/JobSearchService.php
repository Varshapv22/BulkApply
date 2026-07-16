<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JobSearchService
{
    /**
     * Search for jobs using the RapidAPI JSearch API.
     * Free tier: 500 requests/month. Aggregates Indeed, LinkedIn, Glassdoor, etc.
     *
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $location, array $sites = [], int $limit = 20): array
    {
        $apiKey = config('services.jsearch.api_key');

        if (!$apiKey) {
            return ['jobs' => [], 'error' => 'Job search API key not configured. Add RAPIDAPI_KEY to your .env file.'];
        }

        try {
            // Build the query — JSearch supports site filters in the query
            $query = $role;
            if ($location) {
                $query .= ' in ' . $location;
            }

            $params = [
                'query'          => $query,
                'page'           => 1,
                'num_pages'      => 1,
                'date_posted'    => 'month', // jobs from last month
                'remote_jobs_only' => false,
            ];

            $response = Http::timeout(15)
                ->withHeaders([
                    'X-RapidAPI-Key'  => $apiKey,
                    'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com',
                ])
                ->get('https://jsearch.p.rapidapi.com/search-v2', $params);

            if (!$response->successful()) {
                $status = $response->status();
                if ($status === 429) {
                    return ['jobs' => [], 'error' => 'API rate limit exceeded. Free tier allows 500 requests/month.'];
                }
                if ($status === 403) {
                    return ['jobs' => [], 'error' => 'Invalid API key. Check your RAPIDAPI_KEY in .env.'];
                }
                if ($status === 404) {
                    return ['jobs' => [], 'error' => 'Your RapidAPI key is not subscribed to the JSearch API. Subscribe to the free Basic plan at https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch and use that app\'s key.'];
                }
                return ['jobs' => [], 'error' => "API returned status {$status}."];
            }

            $data = $response->json();
            // JSearch v5 /search-v2 nests jobs under data.jobs (was a flat data[] in older versions)
            $results = $data['data']['jobs'] ?? $data['data'] ?? [];

            // Filter by selected sites if specified
            if (!empty($sites)) {
                $siteMap = [
                    'indeed'        => 'indeed.com',
                    'linkedin'      => 'linkedin.com',
                    'glassdoor'     => 'glassdoor.com',
                    'ziprecruiter'  => 'ziprecruiter.com',
                    'dice'          => 'dice.com',
                    'monster'       => 'monster.com',
                    'careerbuilder' => 'careerbuilder.com',
                ];
                $allowedDomains = array_map(fn($s) => $siteMap[$s] ?? $s, $sites);

                $results = array_filter($results, function ($job) use ($allowedDomains) {
                    $url = $job['job_apply_link'] ?? $job['job_google_link'] ?? '';
                    foreach ($allowedDomains as $domain) {
                        if (stripos($url, $domain) !== false) return true;
                    }
                    // Also check employer_website
                    $website = $job['employer_website'] ?? '';
                    foreach ($allowedDomains as $domain) {
                        if (stripos($website, $domain) !== false) return true;
                    }
                    return false;
                });
            }

            $jobs = [];
            foreach (array_slice(array_values($results), 0, $limit) as $item) {
                $jobs[] = $this->normalizeJob($item);
            }

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Job search failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Failed to connect to job search API: ' . $e->getMessage()];
        }
    }

    /**
     * Normalize a JSearch API result to our standard format.
     */
    private function normalizeJob(array $item): array
    {
        // Try to extract a contact email from the description
        $description = $item['job_description'] ?? '';
        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $description, $m)) {
            $email = $m[0];
        }

        // Determine the source site from the apply link
        $applyLink = $item['job_apply_link'] ?? $item['job_google_link'] ?? '';
        $source = 'Unknown';
        $sourceSites = ['indeed.com' => 'Indeed', 'linkedin.com' => 'LinkedIn', 'glassdoor.com' => 'Glassdoor',
                        'ziprecruiter.com' => 'ZipRecruiter', 'dice.com' => 'Dice', 'monster.com' => 'Monster'];
        foreach ($sourceSites as $domain => $name) {
            if (stripos($applyLink, $domain) !== false) {
                $source = $name;
                break;
            }
        }
        if ($source === 'Unknown' && !empty($item['job_publisher'])) {
            $source = $item['job_publisher'];
        }

        return [
            'company'         => $item['employer_name'] ?? 'Unknown',
            'job_title'       => $item['job_title'] ?? 'Unknown',
            'location'        => $item['job_city'] ?? $item['job_state'] ?? ($item['job_is_remote'] ? 'Remote' : ''),
            'recruiter_email' => $email,
            'job_url'         => $applyLink,
            'apply_url'       => $applyLink,
            'source'          => $source,
            'apply_type'      => $email ? 'email' : 'link',
            'description'     => \Illuminate\Support\Str::limit(strip_tags($description), 200),
            'posted'          => $item['job_posted_at_datetime_utc'] ?? null,
            'employer_logo'   => $item['employer_logo'] ?? null,
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobSearchService
{
    /**
     * Search for jobs using the Adzuna API (https://developer.adzuna.com).
     * Free tier: real jobs with location search across many countries.
     *
     * @param  array  $options  ['sort_by' => relevance|date|salary, 'full_time' => bool]
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $location, array $options = [], int $limit = 20): array
    {
        $appId   = config('services.adzuna.app_id');
        $appKey  = config('services.adzuna.app_key');
        $country = config('services.adzuna.country', 'in');

        if (!$appId || !$appKey) {
            return [
                'jobs'  => [],
                'error' => 'Job search API not configured. Add ADZUNA_APP_ID and ADZUNA_APP_KEY to your .env file. Get free credentials at https://developer.adzuna.com.',
            ];
        }

        try {
            // A keyword (company/board/extra term) narrows the query further.
            $what = trim($role . ' ' . ($options['keyword'] ?? ''));

            $params = [
                'app_id'           => $appId,
                'app_key'          => $appKey,
                'what'             => $what,
                'results_per_page' => min(max($limit, 1), 50),
                'content-type'     => 'application/json',
            ];
            if ($location) {
                $params['where'] = $location;
            }
            if (!empty($options['sort_by']) && in_array($options['sort_by'], ['relevance', 'date', 'salary'], true)) {
                $params['sort_by'] = $options['sort_by'];
            }
            if (!empty($options['full_time'])) {
                $params['full_time'] = 1;
            }

            // Adzuna is country-scoped: /v1/api/jobs/{country}/search/{page}
            $response = Http::timeout(15)
                ->get("https://api.adzuna.com/v1/api/jobs/{$country}/search/1", $params);

            if (!$response->successful()) {
                $status     = $response->status();
                $apiMessage = $response->json('exception') ?? $response->json('display') ?? $response->json('message');

                if ($status === 401 || $status === 403) {
                    return ['jobs' => [], 'error' => 'Invalid Adzuna credentials. Check ADZUNA_APP_ID and ADZUNA_APP_KEY in your .env.'];
                }
                if ($status === 429) {
                    return ['jobs' => [], 'error' => 'Adzuna rate limit exceeded. Please try again later.'];
                }
                if ($status === 404) {
                    return ['jobs' => [], 'error' => "No Adzuna coverage for country '{$country}'. Set ADZUNA_COUNTRY in .env to a supported code (e.g. in, us, gb)."];
                }
                return ['jobs' => [], 'error' => $apiMessage
                    ? "Adzuna API error ({$status}): {$apiMessage}"
                    : "Adzuna API returned status {$status}."];
            }

            $results = $response->json('results') ?? [];

            $jobs = [];
            foreach (array_slice($results, 0, $limit) as $item) {
                $jobs[] = $this->normalizeJob($item);
            }

            // Best-effort: find a real company email/website for jobs that arrived
            // without one, by probing the company's own site.
            if (($options['find_contacts'] ?? true) && !empty($jobs)) {
                (new CompanyContactFinder())->enrich($jobs);
            }

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Job search failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Failed to connect to job search API: ' . $e->getMessage()];
        }
    }

    /**
     * Normalize an Adzuna API result to our standard job format.
     */
    private function normalizeJob(array $item): array
    {
        $description = $item['description'] ?? '';

        // Try to extract a contact email from the description (rare, but enables email apply).
        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $description, $m)) {
            $email = $m[0];
        }
        $website = $this->websiteFromEmail($email);

        $applyLink = $item['redirect_url'] ?? '';
        $company   = $item['company']['display_name'] ?? 'Unknown';
        $location  = $item['location']['display_name'] ?? '';

        return [
            'company'         => trim(strip_tags($company)) ?: 'Unknown',
            'job_title'       => trim(strip_tags($item['title'] ?? 'Unknown')),
            'location'        => $location,
            'recruiter_email' => $email,
            'company_email'   => $email,
            'company_website' => $website,
            'company_phone'   => null,
            'job_url'         => $applyLink,
            'apply_url'       => $applyLink,
            'source'          => 'Adzuna',
            'apply_type'      => $email ? 'email' : 'link',
            'description'     => Str::limit(trim(strip_tags($description)), 200),
            'posted'          => $item['created'] ?? null,
            'employer_logo'   => null,
        ];
    }

    /** Derive a company website from an email domain (skips free-mail providers). */
    private function websiteFromEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return null;
        }
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        foreach (['gmail', 'yahoo', 'outlook', 'hotmail', 'rediff', 'live.com', 'icloud', 'protonmail'] as $free) {
            if (str_contains($domain, $free)) {
                return null;
            }
        }
        return $domain ? 'https://' . $domain : null;
    }
}

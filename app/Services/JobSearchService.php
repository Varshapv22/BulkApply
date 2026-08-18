<?php

namespace App\Services;

use App\Models\ApiConfig;
use App\Models\FeatureFlag;
use App\Services\Concerns\FiltersJobResults;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JobSearchService
{
    use FiltersJobResults;

    public function __construct(private JSearchJobService $international)
    {
    }

    /**
     * Search for jobs using the Adzuna API (https://developer.adzuna.com).
     * Free tier: real jobs with location search across many countries.
     *
     * @param  array  $options  ['sort_by' => relevance|date|salary, 'full_time' => bool]
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $location, array $options = [], int $limit = 20): array
    {
        if (!FeatureFlag::enabled('source.adzuna')) {
            return ['jobs' => [], 'error' => 'Web-wide job search is currently disabled by the administrator.'];
        }

        $appId   = ApiConfig::get('adzuna_app_id', config('services.adzuna.app_id'));
        $appKey  = ApiConfig::get('adzuna_app_key', config('services.adzuna.app_key'));

        if (!$appId || !$appKey) {
            return [
                'jobs'  => [],
                'error' => 'Job search API not configured. Add ADZUNA_APP_ID and ADZUNA_APP_KEY to your .env file. Get free credentials at https://developer.adzuna.com.',
            ];
        }

        // Adzuna is country-scoped — "where" only filters *within* one country's
        // index, so a location in a country Adzuna doesn't cover (e.g. Dubai)
        // previously still queried the configured default (India) and returned
        // unrelated results. Resolve the typed location to its real country
        // first; a country Adzuna doesn't index (UAE, other Gulf/Asian markets)
        // goes to JSearch instead, which isn't country-scoped by URL. Only
        // fall back to the configured default when the location is
        // blank/unrecognised (so "Remote" or an unusual place still works).
        $resolvedCountry = LocationDirectory::resolveCountry($location);
        if ($resolvedCountry !== null && !LocationDirectory::isAdzunaSupported($resolvedCountry)) {
            return $this->international->search($role, $location, $resolvedCountry, $options, $limit);
        }
        $country = $resolvedCountry ?? ApiConfig::get('adzuna_country', config('services.adzuna.country', 'in'));

        try {
            // A keyword (board/extra term) narrows the query further.
            // Note: company names are passed via options['company'] — NOT
            // merged into what_and — so the role relevance stays tight.
            $what = trim($role . ' ' . ($options['keyword'] ?? ''));

            $params = [
                'app_id'           => $appId,
                'app_key'          => $appKey,
                // what_and requires EVERY word to appear in the ad (title or body).
                // Plain "what" only requires ANY word, which let unrelated roles
                // (e.g. "Business Development") match a "Laravel Developer" search.
                'what_and'         => $what,
                // Always fetch the API max, then filter+slice client-side (see
                // below) — filtering can drop a chunk of loosely-matched ads,
                // so under-fetching would return fewer jobs than $limit.
                'results_per_page' => 50,
                'content-type'     => 'application/json',
            ];
            if ($location) {
                $params['where'] = $location;
            }
            // Company name filter — Adzuna's dedicated param keeps the role
            // query clean (avoids "laravel wipro" in what_and returning zero).
            if (!empty($options['company'])) {
                $params['company'] = $options['company'];
            }
            if (!empty($options['sort_by']) && in_array($options['sort_by'], ['relevance', 'date', 'salary'], true)) {
                $params['sort_by'] = $options['sort_by'];
            }
            if (!empty($options['full_time'])) {
                $params['full_time'] = 1;
            }

            // Adzuna is country-scoped: /v1/api/jobs/{country}/search/{page}
            $response = Http::timeout(8)->connectTimeout(4)
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

            $jobs = array_map(fn ($item) => $this->normalizeJob($item), $results);

            // Adzuna aggregates from staffing/recruitment agencies as well as direct
            // employers. Users searching without a specific platform want real
            // vacancies, not agencies bulk-posting on a client's behalf.
            $jobs = $this->filterOutAgencies($jobs);

            // Require at least one significant role word to actually appear in
            // the ad (title or body) as a final relevance sanity check.
            $jobs = $this->filterByTitleRelevance($jobs, $role);
            $jobs = array_slice($jobs, 0, $limit);
            $jobs = $this->truncateDescriptions($jobs);

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

        $applyLink = $this->sanitizeApplyUrl($item['redirect_url'] ?? '');
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
            // Kept full (not yet truncated) so downstream relevance/agency
            // filters can see the whole ad, not just its first 200 chars.
            // truncateDescriptions() shortens it for display after filtering.
            'description'     => trim(strip_tags($description)),
            'posted'          => $item['created'] ?? null,
            'employer_logo'   => null,
        ];
    }

    /**
     * Sanitize an Adzuna redirect_url into the best available direct link.
     *
     * Adzuna sometimes returns the original platform's search-page URL as
     * redirect_url (e.g. an Indeed /jobs?q=…&vjk= URL or a Google Jobs
     * /search?ibp=htl;jobs URL) instead of a direct job listing URL.
     * Clicking those lands the user on a search/listing page, not the job ad.
     *
     * - Indeed:  /jobs?…&vjk=ID  →  /viewjob?jk=ID  (direct listing page)
     * - Google Jobs: strip back to google.com/search?ibp=htl;jobs (usable but
     *   still shows the Google Jobs panel — keep as-is, nothing better exists)
     * - Everything else: return unchanged.
     */
    private function sanitizeApplyUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $parsed = parse_url($url);
        $host   = strtolower($parsed['host'] ?? '');
        $path   = $parsed['path'] ?? '';

        // Indeed: /jobs?q=…&vjk=ID  →  direct viewjob URL
        if (str_contains($host, 'indeed.com') && str_starts_with($path, '/jobs')) {
            parse_str($parsed['query'] ?? '', $qs);
            $vjk = $qs['vjk'] ?? '';
            if ($vjk !== '') {
                $scheme = str_starts_with($host, 'in.') ? 'https://in.indeed.com' : 'https://www.indeed.com';
                return "{$scheme}/viewjob?jk={$vjk}";
            }
        }

        return $url;
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

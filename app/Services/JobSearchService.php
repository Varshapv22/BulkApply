<?php

namespace App\Services;

use App\Models\ApiConfig;
use App\Models\FeatureFlag;
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
        if (!FeatureFlag::enabled('source.adzuna')) {
            return ['jobs' => [], 'error' => 'Web-wide job search is currently disabled by the administrator.'];
        }

        $appId   = ApiConfig::get('adzuna_app_id', config('services.adzuna.app_id'));
        $appKey  = ApiConfig::get('adzuna_app_key', config('services.adzuna.app_key'));
        $country = ApiConfig::get('adzuna_country', config('services.adzuna.country', 'in'));

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
     * Keep only jobs whose title OR description contains at least one
     * significant word from the searched role. A title match is the
     * strongest signal, but most direct-employer ads use a generic title
     * (e.g. "PHP Developer") and name the actual stack ("Laravel") only in
     * the body — restricting to the title alone was discarding those (real,
     * Adzuna-verified-relevant) ads and left only the handful that happened
     * to spell the role out in the title. Falls back to the unfiltered list
     * if nothing would survive (e.g. an unusual role phrase), so a search
     * never returns zero results because of this heuristic alone.
     */
    private function filterByTitleRelevance(array $jobs, string $role): array
    {
        $tokens = array_values(array_unique(array_filter(
            preg_split('/[\s\/,]+/', mb_strtolower(trim($role))),
            fn ($t) => mb_strlen($t) >= 3
        )));
        if (empty($tokens)) {
            return $jobs;
        }

        $filtered = array_values(array_filter($jobs, function ($job) use ($tokens) {
            $haystack = mb_strtolower(($job['job_title'] ?? '') . ' ' . ($job['description'] ?? ''));
            foreach ($tokens as $t) {
                if (str_contains($haystack, $t)) return true;
            }
            return false;
        }));

        return $filtered ?: $jobs;
    }

    /**
     * Drop ads posted by staffing/recruitment/consultancy agencies so only
     * direct employer vacancies remain. Unlike filterByTitleRelevance, this
     * has no "fall back to unfiltered" safety net — showing an agency ad
     * because the alternative is fewer results defeats the point of the filter.
     */
    private function filterOutAgencies(array $jobs): array
    {
        // Company-name signals: the agency's own name usually gives it away.
        $companyKeywords = [
            'staffing', 'consultanc', 'recruit', 'placement', 'manpower',
            'outsourc', 'talent acquisition', 'talent solutions', 'hr solutions',
            'hr services', 'workforce solutions', 'people solutions', 'headhunt',
            'hiring partner', 'jobs portal', 'career solutions',
        ];

        // Description-phrase signals: how an agency ad reads even when the
        // company name itself looks neutral (e.g. "Confidential").
        $descriptionKeywords = [
            'on behalf of our client', 'on behalf of one of our client',
            'one of our clients', 'one of our esteemed clients', 'multiple clients',
            'leading recruitment', 'staffing solutions', 'placement consultancy',
            'placement services', 'our client is looking', 'our client is hiring',
            'panel of clients', 'reputed client', 'client company', 'mnc client',
            'recruitment agency', 'recruitment firm', 'staffing agency',
        ];

        return array_values(array_filter($jobs, function ($job) use ($companyKeywords, $descriptionKeywords) {
            $company = mb_strtolower($job['company'] ?? '');
            foreach ($companyKeywords as $kw) {
                if (str_contains($company, $kw)) {
                    return false;
                }
            }

            $description = mb_strtolower($job['description'] ?? '');
            foreach ($descriptionKeywords as $kw) {
                if (str_contains($description, $kw)) {
                    return false;
                }
            }

            return true;
        }));
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
            // Kept full (not yet truncated) so downstream relevance/agency
            // filters can see the whole ad, not just its first 200 chars.
            // truncateDescriptions() shortens it for display after filtering.
            'description'     => trim(strip_tags($description)),
            'posted'          => $item['created'] ?? null,
            'employer_logo'   => null,
        ];
    }

    /** Shorten descriptions to display length. Run only after all filtering is done. */
    private function truncateDescriptions(array $jobs): array
    {
        return array_map(function ($job) {
            $job['description'] = Str::limit($job['description'] ?? '', 200);
            return $job;
        }, $jobs);
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

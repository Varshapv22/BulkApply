<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Runs a role/location/site search through the right source (aggregated web
 * search, a tech-park scraper, or a named site/company), caching the result.
 *
 * This is the single place both the manual "Find Jobs" search and the
 * saved-search alert checker go through, so a saved search and a manual
 * re-search for the same query share one cached result instead of each
 * hitting Adzuna/the tech-park scrapers independently.
 */
class JobQueryService
{
    /** How long a given (role, location, site, options) query's results are reused. */
    private const CACHE_TTL_HOURS = 2;

    public function __construct(
        private JobSearchService $aggregator,
        private SiteJobService $siteService,
    ) {
    }

    /**
     * @param  array  $options  ['sort_by' => relevance|date|salary, 'full_time' => bool, 'find_contacts' => bool]
     * @return array{jobs: array, error: ?string}
     */
    public function run(string $role, string $location, string $site, array $options = [], int $limit = 30, string $company = ''): array
    {
        $role    = trim($role);
        $site    = trim($site);
        $company = trim($company);

        $key = 'jobsearch:' . md5(implode('|', [
            mb_strtolower($role),
            mb_strtolower($location),
            mb_strtolower($site),
            mb_strtolower($company),
            $options['sort_by'] ?? 'relevance',
            !empty($options['full_time']) ? '1' : '0',
            // find_contacts changes the shape of the result (adds company
            // email/website enrichment) — keyed separately so a background
            // alert check (always find_contacts=false) can't cache a
            // contacts-free result that a manual search then reuses.
            !empty($options['find_contacts']) ? '1' : '0',
        ]));

        return Cache::remember($key, now()->addHours(self::CACHE_TTL_HOURS), function () use ($role, $location, $site, $options, $limit, $company) {
            return $this->execute($role, $location, $site, $options, $limit, $company);
        });
    }

    /** @return array{jobs: array, error: ?string} */
    private function execute(string $role, string $location, string $site, array $options, int $limit, string $company): array
    {
        if ($site !== '') {
            // The candidate named a specific site/company.
            $siteResult = $this->siteService->search($role, $site, $limit);

            if ($siteResult['handled'] && !empty($siteResult['jobs'])) {
                // We read the site directly.
                return ['jobs' => $siteResult['jobs'], 'error' => $siteResult['error']];
            }

            if ($siteResult['handled']) {
                // Recognised a site/URL but got nothing usable (JS-rendered etc.)
                // Fall back to Adzuna. For company names, try the dedicated company
                // filter first (keeps role query clean), then retry with keyword if
                // that returns nothing (Adzuna's company param can be strict).
                $siteKeyword = $this->siteKeyword($site);

                if ($this->isLikelyCompanyName($site)) {
                    $agg = $this->aggregator->search($role, $location, $options + ['company' => $siteKeyword], $limit);
                    if (empty($agg['jobs'])) {
                        // Company filter too strict — broaden to keyword search.
                        $agg = $this->aggregator->search($role, $location, $options + ['keyword' => $siteKeyword], $limit);
                    }
                } else {
                    $agg = $this->aggregator->search($role, $location, $options + ['keyword' => $siteKeyword], $limit);
                }

                // When we have results, show them cleanly with no error message.
                // Only surface the error when there is truly nothing to show.
                return !empty($agg['jobs'])
                    ? ['jobs' => $agg['jobs'], 'error' => null]
                    : ['jobs' => [], 'error' => $siteResult['error'] ?? ($agg['error'] ?? 'No jobs found.')];
            }

            if (!empty($siteResult['platform'])) {
                // A big platform (Indeed/Naukri/LinkedIn…) we can't scrape, but the
                // aggregator already indexes jobs from it — search role + location.
                $agg = $this->aggregator->search($role, $location, $options, $limit);
                $note = $siteResult['platform'] . " can't be read directly (it blocks automated access), so these are matching jobs aggregated from across the web — many originate from " . $siteResult['platform'] . '.';
                return !empty($agg['jobs'])
                    ? ['jobs' => $agg['jobs'], 'error' => $note]
                    : ['jobs' => [], 'error' => $agg['error'] ?? 'No jobs found.'];
            }

            // A bare name we can't fetch — aggregated search with it as keyword.
            return $this->aggregator->search($role, $location, $options + ['keyword' => $site], $limit);
        }

        // When a company name is given (with no explicit site), try to scrape
        // that company's own careers page first, then supplement with Adzuna.
        if ($company !== '') {
            $careerResult = $this->siteService->search($role, $company, $limit);

            if ($careerResult['handled'] && !empty($careerResult['jobs'])) {
                // Got live listings from the company's own careers page — prepend
                // them to an Adzuna search (filtered by company) so the user sees
                // both direct postings and aggregated listings.
                $agg = $this->aggregator->search($role, $location, $options + ['company' => $company], $limit);
                if (empty($agg['jobs'])) {
                    $agg = $this->aggregator->search($role, $location, $options + ['keyword' => $company], $limit);
                }
                $combined = array_merge($careerResult['jobs'], $agg['jobs'] ?? []);
                $seen = [];
                $deduped = [];
                foreach ($combined as $job) {
                    $dedupKey = mb_strtolower(($job['company'] ?? '') . '|' . ($job['job_title'] ?? ''));
                    if (!isset($seen[$dedupKey])) {
                        $seen[$dedupKey] = true;
                        $deduped[] = $job;
                    }
                }
                return ['jobs' => array_slice($deduped, 0, $limit), 'error' => null];
            }

            // Career page not reachable / no structured data — fall back to
            // Adzuna using the company name as a dedicated company filter so
            // only that employer's ads come back (keeps the role query clean).
            $agg = $this->aggregator->search($role, $location, $options + ['company' => $company], $limit);
            if (empty($agg['jobs'])) {
                $agg = $this->aggregator->search($role, $location, $options + ['keyword' => $company], $limit);
            }
            return $agg;
        }

        // Plain aggregated search across the whole web.
        return $this->aggregator->search($role, $location, $options, $limit);
    }

    /**
     * Returns true when $site looks like a bare company name rather than a
     * URL or tech-park name — used to decide whether to use Adzuna's company
     * filter vs. a generic keyword when the career-page scrape fails.
     */
    private function isLikelyCompanyName(string $site): bool
    {
        // URLs and domain-like strings are not company names.
        if (Str::startsWith($site, ['http://', 'https://'])) {
            return false;
        }
        if (!str_contains($site, ' ') && preg_match('/\.[a-z]{2,}(\/|$)/i', $site)) {
            return false;
        }
        // Known tech-park names are not company names.
        $parks = ['technopark', 'infopark', 'cyberpark', 'kinfra', 'smart city', 'malabar business'];
        $lower = mb_strtolower($site);
        foreach ($parks as $p) {
            if (str_contains($lower, $p)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Derive a search keyword from a site input (URL or name).
     * e.g. "https://infopark.in/jobs" -> "infopark".
     */
    private function siteKeyword(string $site): string
    {
        $isUrlish = Str::startsWith($site, ['http://', 'https://'])
            || (!str_contains($site, ' ') && preg_match('/\.[a-z]{2,}(\/|$)/i', $site));

        if ($isUrlish) {
            $url = Str::startsWith($site, ['http://', 'https://']) ? $site : 'https://' . $site;
            $host = parse_url($url, PHP_URL_HOST) ?: $site;
            $host = preg_replace('/^www\./', '', $host);
            return explode('.', $host)[0] ?: $host;
        }

        return $site;
    }
}

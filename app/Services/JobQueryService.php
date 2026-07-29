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
    public function run(string $role, string $location, string $site, array $options = [], int $limit = 30): array
    {
        $role = trim($role);
        $site = trim($site);

        $key = 'jobsearch:' . md5(implode('|', [
            mb_strtolower($role),
            mb_strtolower($location),
            mb_strtolower($site),
            $options['sort_by'] ?? 'relevance',
            !empty($options['full_time']) ? '1' : '0',
            // find_contacts changes the shape of the result (adds company
            // email/website enrichment) — keyed separately so a background
            // alert check (always find_contacts=false) can't cache a
            // contacts-free result that a manual search then reuses.
            !empty($options['find_contacts']) ? '1' : '0',
        ]));

        return Cache::remember($key, now()->addHours(self::CACHE_TTL_HOURS), function () use ($role, $location, $site, $options, $limit) {
            return $this->execute($role, $location, $site, $options, $limit);
        });
    }

    /** @return array{jobs: array, error: ?string} */
    private function execute(string $role, string $location, string $site, array $options, int $limit): array
    {
        if ($site !== '') {
            // The candidate named a specific site/company.
            $siteResult = $this->siteService->search($role, $site, $limit);

            if ($siteResult['handled'] && !empty($siteResult['jobs'])) {
                // We read the site directly.
                return ['jobs' => $siteResult['jobs'], 'error' => $siteResult['error']];
            }

            if ($siteResult['handled']) {
                // Recognised a site/URL but got nothing usable — fall back to the
                // aggregated index (using the site's name as a keyword) with a note.
                $agg = $this->aggregator->search($role, $location, $options + ['keyword' => $this->siteKeyword($site)], $limit);
                $note = $siteResult['error'] ?: 'No listings could be read from that site.';
                return !empty($agg['jobs'])
                    ? ['jobs' => $agg['jobs'], 'error' => $note . ' Showing related jobs from across the web instead.']
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

        // Plain aggregated search across the whole web.
        return $this->aggregator->search($role, $location, $options, $limit);
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

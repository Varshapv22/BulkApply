<?php

namespace App\Services;

use App\Models\ApiConfig;
use App\Models\FeatureFlag;
use App\Services\Concerns\FiltersJobResults;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Searches jobs via JSearch (RapidAPI — aggregates Google for Jobs), used for
 * any country Adzuna doesn't index (UAE and the rest of the Gulf, and most of
 * Asia beyond India/Singapore). Unlike Adzuna, it isn't scoped by URL — a
 * "country" query param narrows results within one global endpoint, so this
 * covers whichever country JobSearchService has already resolved the typed
 * location to (see LocationDirectory).
 *
 * @see JobSearchService::search() for the routing decision.
 */
class JSearchJobService
{
    use FiltersJobResults;

    private const BASE_URL = 'https://jsearch.p.rapidapi.com/search-v2';

    /**
     * @param  array  $options  ['keyword' => string, 'company' => string, 'find_contacts' => bool]
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $location, string $countryCode, array $options = [], int $limit = 20): array
    {
        if (!FeatureFlag::enabled('source.jsearch')) {
            return $this->notCoveredResult($countryCode);
        }

        $apiKey = ApiConfig::get('jsearch_api_key', config('services.jsearch.api_key'));
        if (!$apiKey) {
            return $this->notCoveredResult($countryCode);
        }

        try {
            $extra = trim($options['keyword'] ?? $options['company'] ?? '');
            $query = trim("{$role} {$extra}") . " in {$location}";

            $response = Http::timeout(10)->connectTimeout(4)
                ->withHeaders([
                    'X-RapidAPI-Key'  => $apiKey,
                    'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com',
                ])
                ->get(self::BASE_URL, [
                    'query'     => $query,
                    'country'   => $countryCode,
                    'language'  => 'en',
                    'num_pages' => 1,
                ]);

            if (!$response->successful()) {
                $status = $response->status();
                if ($status === 401 || $status === 403) {
                    return ['jobs' => [], 'error' => 'Invalid JSearch (RapidAPI) credentials. Check RAPIDAPI_KEY in your .env.'];
                }
                if ($status === 429) {
                    return ['jobs' => [], 'error' => 'JSearch rate limit exceeded. Please try again later.'];
                }
                return ['jobs' => [], 'error' => "JSearch API returned status {$status}."];
            }

            $results = $response->json('data.jobs') ?? [];

            $jobs = array_map(fn ($item) => $this->normalizeJob($item), $results);
            $jobs = $this->filterOutAgencies($jobs);
            $jobs = $this->filterByTitleRelevance($jobs, $role);
            $jobs = array_slice($jobs, 0, $limit);
            $jobs = $this->truncateDescriptions($jobs);

            if (($options['find_contacts'] ?? true) && !empty($jobs)) {
                (new CompanyContactFinder())->enrich($jobs);
            }

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('JSearch job search failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Failed to connect to JSearch API: ' . $e->getMessage()];
        }
    }

    private function notCoveredResult(string $countryCode): array
    {
        $place = LocationDirectory::countryLabel($countryCode);
        return [
            'jobs'  => [],
            'error' => "Our web-wide search doesn't cover {$place} yet. Try naming a specific employer in the \"Company\" field, or a careers-page URL in \"Job site, platform or company\" — those work for any country.",
        ];
    }

    /** Normalize a JSearch (RapidAPI) result to our standard job format. */
    private function normalizeJob(array $item): array
    {
        $description = $item['job_description'] ?? '';

        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $description, $m)) {
            $email = $m[0];
        }

        $locationParts = array_filter([$item['job_city'] ?? null, $item['job_state'] ?? null, $item['job_country'] ?? null]);
        $location = $locationParts ? implode(', ', $locationParts) : ($item['job_location'] ?? '');

        $applyLink = $item['job_apply_link'] ?? ($item['apply_options'][0]['apply_link'] ?? '');

        return [
            'company'         => trim(strip_tags($item['employer_name'] ?? '')) ?: 'Unknown',
            'job_title'       => trim(strip_tags($item['job_title'] ?? 'Unknown')),
            'location'        => $location,
            'recruiter_email' => $email,
            'company_email'   => $email,
            'company_website' => $item['employer_website'] ?? null,
            'company_phone'   => null,
            'job_url'         => $applyLink,
            'apply_url'       => $applyLink,
            'source'          => 'JSearch' . (!empty($item['job_publisher']) ? " ({$item['job_publisher']})" : ''),
            'apply_type'      => $email ? 'email' : 'link',
            'description'     => trim(strip_tags($description)),
            'posted'          => $item['job_posted_at'] ?? null,
            'employer_logo'   => $item['employer_logo'] ?? null,
        ];
    }
}

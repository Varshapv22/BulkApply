<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads jobs from the Technopark (Trivandrum) board.
 * List comes from a JSON API (/api/paginated-jobs); each job's company email,
 * website and phone live on the Inertia detail page (/job-details/{id}), which
 * we fetch concurrently and parse from its embedded data-page JSON.
 */
class TechnoparkJobService
{
    private const API  = 'https://technopark.in/api/paginated-jobs';
    private const BASE = 'https://technopark.in';
    private const MAX_PAGES = 6;   // API returns 20/page
    private const MAX_ENRICH = 24; // detail pages fetched per search (concurrent)
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /**
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $keyword = '', int $limit = 30): array
    {
        $search = trim($role . ' ' . $keyword);

        try {
            $first = $this->fetchPage($search, 1);
            if ($first === null) {
                return ['jobs' => [], 'error' => 'Could not reach the Technopark job board right now. Please try again shortly.'];
            }

            $lastPage = min((int) ($first['last_page'] ?? 1), self::MAX_PAGES);
            $rows = $first['data'] ?? [];

            if ($lastPage > 1) {
                $pages = range(2, $lastPage);
                $responses = Http::pool(fn ($pool) => array_map(
                    fn ($p) => $pool->as((string) $p)->timeout(15)->withHeaders($this->headers())
                        ->get(self::API, ['search' => $search, 'page' => $p]),
                    $pages
                ));
                foreach ($pages as $p) {
                    $resp = $responses[(string) $p] ?? null;
                    if ($resp && !($resp instanceof \Throwable) && $resp->ok()) {
                        $rows = array_merge($rows, $resp->json('data') ?? []);
                    }
                }
            }

            $jobs = [];
            foreach ($rows as $row) {
                $jobs[] = $this->normalize($row);
                if (count($jobs) >= $limit) {
                    break;
                }
            }

            $this->enrichContacts($jobs);

            foreach ($jobs as &$j) {
                unset($j['_id']);
            }
            unset($j);

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Technopark fetch failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Could not fetch Technopark jobs: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch each job's detail page concurrently and fill in company
     * email / website / phone (and make email jobs directly applicable).
     */
    private function enrichContacts(array &$jobs): void
    {
        $targets = array_slice(array_keys($jobs), 0, self::MAX_ENRICH);
        $targets = array_values(array_filter($targets, fn ($i) => !empty($jobs[$i]['_id'])));
        if (empty($targets)) {
            return;
        }

        try {
            $responses = Http::pool(fn ($pool) => array_map(
                fn ($i) => $pool->as((string) $i)->timeout(15)->withHeaders($this->headers())
                    ->get(self::BASE . '/job-details/' . $jobs[$i]['_id']),
                $targets
            ));
        } catch (\Throwable $e) {
            Log::warning('Technopark enrich failed', ['error' => $e->getMessage()]);
            return;
        }

        foreach ($targets as $i) {
            $resp = $responses[(string) $i] ?? null;
            if (!$resp || $resp instanceof \Throwable || !$resp->ok()) {
                continue;
            }
            $listing = $this->parseListing($resp->body());
            if (!$listing) {
                continue;
            }

            $email   = $listing['contact_email'] ?? ($listing['company']['email'] ?? null);
            $website = $listing['company']['website'] ?? null;
            $phone   = $listing['company']['phone'] ?? null;

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $jobs[$i]['company_email']   = $email;
                $jobs[$i]['recruiter_email'] = $email;
                $jobs[$i]['apply_type']      = 'email';
            }
            if ($website) {
                $jobs[$i]['company_website'] = $website;
            }
            if ($phone) {
                $jobs[$i]['company_phone'] = $phone;
            }
        }
    }

    /** Pull the jobListing object out of the detail page's Inertia data-page JSON. */
    private function parseListing(string $html): ?array
    {
        if (!preg_match('/data-page="([^"]+)"/s', $html, $m)) {
            return null;
        }
        $json = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        return $json['props']['jobListing'] ?? null;
    }

    private function fetchPage(string $search, int $page): ?array
    {
        $resp = Http::timeout(15)->withHeaders($this->headers())
            ->get(self::API, ['search' => $search, 'page' => $page]);

        return $resp->ok() ? $resp->json() : null;
    }

    private function headers(): array
    {
        return [
            'User-Agent'       => self::UA,
            'Accept'           => 'application/json, text/plain, */*',
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer'          => self::BASE . '/job-search',
        ];
    }

    private function normalize(array $row): array
    {
        $company = $row['company']['company'] ?? 'Unknown';
        $logo    = $row['company']['logo'] ?? null;
        $id      = $row['id'] ?? null;
        $title   = $row['job_title'] ?? 'Unknown';

        $url = $id
            ? self::BASE . '/job-details/' . $id . '?job=' . rawurlencode($title)
            : self::BASE . '/job-search';

        $desc = 'Posted ' . ($row['posted_date'] ?? '—');
        if (!empty($row['closing_date'])) {
            $desc .= ' · Apply by ' . $row['closing_date'];
        }
        if (!empty($row['is_walk_in'])) {
            $desc .= ' · Walk-in';
        }

        return [
            'company'         => $company,
            'job_title'       => $title,
            'location'        => 'Technopark, Trivandrum, Kerala',
            'recruiter_email' => null,
            'company_email'   => null,
            'company_website' => null,
            'company_phone'   => null,
            'job_url'         => $url,
            'apply_url'       => $url,
            'source'          => 'Technopark',
            'apply_type'      => 'link',
            'description'     => $desc,
            'posted'          => $row['posted_date'] ?? null,
            'employer_logo'   => $logo ? self::BASE . $logo : null,
            '_id'             => $id,
        ];
    }
}

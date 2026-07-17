<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads jobs from the Technopark (Trivandrum) board.
 * The site is a JS/Inertia app, but its React component fetches from a plain
 * JSON API — https://technopark.in/api/paginated-jobs?search=&page= — which we
 * call directly (server-side scraping of the rendered HTML is impossible there).
 */
class TechnoparkJobService
{
    private const API  = 'https://technopark.in/api/paginated-jobs';
    private const BASE = 'https://technopark.in';
    private const MAX_PAGES = 6; // API returns 20/page
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /**
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $keyword = '', int $limit = 30): array
    {
        $search = trim($role . ' ' . $keyword);

        try {
            // Fetch page 1 to learn how many pages exist.
            $first = $this->fetchPage($search, 1);
            if ($first === null) {
                return ['jobs' => [], 'error' => 'Could not reach the Technopark job board right now. Please try again shortly.'];
            }

            $lastPage = min((int) ($first['last_page'] ?? 1), self::MAX_PAGES);
            $rows = $first['data'] ?? [];

            // Fetch the remaining pages concurrently.
            if ($lastPage > 1) {
                $pages = range(2, $lastPage);
                $responses = Http::pool(fn ($pool) => array_map(
                    fn ($p) => $pool->as((string) $p)
                        ->timeout(15)
                        ->withHeaders($this->headers())
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

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Technopark fetch failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Could not fetch Technopark jobs: ' . $e->getMessage()];
        }
    }

    private function fetchPage(string $search, int $page): ?array
    {
        $resp = Http::timeout(15)
            ->withHeaders($this->headers())
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
            'job_url'         => $url,
            'apply_url'       => $url,
            'source'          => 'Technopark',
            'apply_type'      => 'link',
            'description'     => $desc,
            'posted'          => $row['posted_date'] ?? null,
            'employer_logo'   => $logo ? self::BASE . $logo : null,
        ];
    }
}

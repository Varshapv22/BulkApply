<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fetches jobs from the KINFRA Hi-Tech Park Kozhikode website (kinfra.org).
 * KINFRA does not operate a dedicated job-board like Technopark/Infopark, so
 * we try their known vacancies / careers pages and fall back to the generic
 * JSON-LD + heuristic link extractor used by SiteJobService.
 */
class KinfraJobService
{
    private const BASE = 'https://kinfra.org';
    private const UA   = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    private const PAGES = [
        '/vacancies',
        '/jobs',
        '/career',
        '/careers',
        '/job-openings',
        '/recruitment',
    ];

    /** @return array{jobs: array, error: ?string} */
    public function search(string $role, string $keyword = '', int $limit = 30): array
    {
        $role    = trim($role);
        $keyword = trim($keyword);

        try {
            $responses = Http::pool(fn ($pool) => array_map(
                fn ($path) => $pool->as($path)
                    ->timeout(15)
                    ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
                    ->withOptions(['allow_redirects' => ['max' => 4]])
                    ->get(self::BASE . $path),
                self::PAGES
            ));

            $jobs = [];
            foreach (self::PAGES as $path) {
                $resp = $responses[$path] ?? null;
                if (!$resp || $resp instanceof \Throwable || !$resp->successful()) {
                    continue;
                }
                $pageUrl = self::BASE . $path;
                $found   = $this->extractJobs($resp->body(), $pageUrl, $role, $keyword, $limit - count($jobs));
                $jobs    = array_merge($jobs, $found);
                if (count($jobs) >= $limit) {
                    break;
                }
            }

            if (empty($jobs)) {
                return ['jobs' => [], 'error' => 'No current vacancies could be read from the KINFRA website. Their listings may require a manual visit to kinfra.org.'];
            }

            return ['jobs' => array_slice($jobs, 0, $limit), 'error' => null];

        } catch (\Throwable $e) {
            Log::error('KinfraJobService fetch failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Could not fetch KINFRA jobs: ' . $e->getMessage()];
        }
    }

    private function extractJobs(string $html, string $pageUrl, string $role, string $keyword, int $limit): array
    {
        $jobs = [];

        // 1. JSON-LD JobPosting blocks
        preg_match_all(
            '#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',
            $html,
            $matches
        );
        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            if (!is_array($data)) {
                continue;
            }
            foreach ($this->collectPostings($data) as $jp) {
                $hay = mb_strtolower(($jp['title'] ?? '') . ' ' . ($jp['description'] ?? ''));
                if (!$this->matches($hay, $role, $keyword)) {
                    continue;
                }
                $jobs[] = $this->normalizePosting($jp, $pageUrl);
                if (count($jobs) >= $limit) {
                    return $jobs;
                }
            }
        }
        if (!empty($jobs)) {
            return $jobs;
        }

        // 2. Heuristic <a> link extraction
        preg_match_all('#<a\b[^>]+href=["\']([^"\']*)["\'][^>]*>([\s\S]*?)</a>#i', $html, $links, PREG_SET_ORDER);
        $seen = [];
        foreach ($links as $m) {
            $href = html_entity_decode(trim($m[1]));
            $text = trim(strip_tags($m[2]));

            if (mb_strlen($text) < 5 || mb_strlen($text) > 150) {
                continue;
            }
            if (preg_match('/^(home|about|contact|blog|news|login|menu|more|next|prev|back|apply now|click here|read more|view all|careers|jobs|openings)$/i', $text)) {
                continue;
            }
            if (str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || $href === '') {
                continue;
            }

            if (!preg_match('#^https?://#i', $href)) {
                $href = self::BASE . '/' . ltrim($href, '/');
            }

            $path = strtolower(parse_url($href, PHP_URL_PATH) ?? '');
            if (!preg_match('#/(job|jobs|career|careers|vacanc|opening|recruitment|position|role)[/\-_]#', $path)) {
                continue;
            }

            $key = mb_strtolower($text);
            if (isset($seen[$key])) {
                continue;
            }

            $hay = mb_strtolower($text);
            if (!$this->matches($hay, $role, $keyword)) {
                continue;
            }

            $seen[$key] = true;
            $jobs[] = [
                'company'         => 'KINFRA Hi-Tech Park',
                'job_title'       => $text,
                'location'        => 'KINFRA Hi-Tech Park, Kozhikode, Kerala',
                'recruiter_email' => null,
                'company_email'   => null,
                'company_website' => self::BASE,
                'company_phone'   => null,
                'job_url'         => $href,
                'apply_url'       => $href,
                'source'          => 'KINFRA',
                'apply_type'      => 'link',
                'description'     => '',
                'posted'          => null,
                'employer_logo'   => null,
            ];
            if (count($jobs) >= $limit) {
                return $jobs;
            }
        }

        return $jobs;
    }

    private function collectPostings(array $node): array
    {
        $out  = [];
        $type = $node['@type'] ?? null;
        if ((is_string($type) && strtolower($type) === 'jobposting')
            || (is_array($type) && in_array('JobPosting', $type, true))) {
            $out[] = $node;
            return $out;
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $out = array_merge($out, $this->collectPostings($v));
            }
        }
        return $out;
    }

    private function normalizePosting(array $jp, string $pageUrl): array
    {
        $org     = $jp['hiringOrganization'] ?? null;
        $company = is_array($org) ? ($org['name'] ?? 'KINFRA') : (is_string($org) ? $org : 'KINFRA');

        $desc  = strip_tags(is_string($jp['description'] ?? null) ? $jp['description'] : '');
        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $desc, $m)) {
            $email = $m[0];
        }

        $url = is_string($jp['url'] ?? null) ? $jp['url'] : $pageUrl;

        return [
            'company'         => $company,
            'job_title'       => is_string($jp['title'] ?? null) ? trim($jp['title']) : 'Unknown',
            'location'        => 'KINFRA Hi-Tech Park, Kozhikode, Kerala',
            'recruiter_email' => $email,
            'company_email'   => $email,
            'company_website' => self::BASE,
            'company_phone'   => null,
            'job_url'         => $url,
            'apply_url'       => $url,
            'source'          => 'KINFRA',
            'apply_type'      => $email ? 'email' : 'link',
            'description'     => Str::limit(trim($desc), 200),
            'posted'          => $jp['datePosted'] ?? null,
            'employer_logo'   => null,
        ];
    }

    private function matches(string $hay, string $role, string $keyword): bool
    {
        if ($keyword !== '' && !str_contains($hay, mb_strtolower($keyword))) {
            return false;
        }
        if ($role !== '') {
            $tokens = array_filter(
                preg_split('/\s+/', mb_strtolower($role)),
                fn ($t) => mb_strlen($t) >= 3
            );
            if ($tokens) {
                foreach ($tokens as $t) {
                    if (str_contains($hay, $t)) {
                        return true;
                    }
                }
                return false;
            }
        }
        return true;
    }
}
